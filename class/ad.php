<?php
/**
 * Copyright (C) 2006-2009 Gregory Meiste
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @package Ads
 * @author Greg Meiste <greg.meiste+github@gmail.com>
 */

class Ads_Ad
{
    var $id             = 0;
    var $campaign_id    = 0;
    var $title          = NULL;
    var $ad_type        = 0;
    var $filename       = NULL;
    var $width          = NULL;
    var $height         = NULL;
    var $ad_text        = NULL;
    var $url            = NULL;
    var $active         = 1;
    var $approved       = 0;
    var $created        = 0;


    function Ads_Ad($id=NULL)
    {
        if (!empty($id))
        {
            $this->setId($id);

            $db = new PHPWS_DB('ads');
            $db->loadObject($this);
        }
    }

    function setId($id)
    {
        $this->id = (int)$id;
    }

    function getId()
    {
        return $this->id;
    }

    function setCampaignId($campaign_id)
    {
        $this->campaign_id = (int)$campaign_id;
    }

    function getCampaignId()
    {
        return $this->campaign_id;
    }

    function setTitle($title)
    {
        $this->title = strip_tags($title);
    }

    function getTitle()
    {
        return $this->title;
    }

    function setAdType($ad_type)
    {
        $this->ad_type = (int)$ad_type;
    }

    function getAdType()
    {
        if ($this->ad_type == 0)
        {
            return dgettext('ads', 'Banner');
        }
        else
        {
            return dgettext('ads', 'Text Ad');
        }
    }

    function setFilename($filename)
    {
        $this->filename = $filename;
    }

    function getFilename()
    {
        return $this->filename;
    }

    function setWidth($width)
    {
        $this->width = (int)$width;
    }

    function getWidth()
    {
        return $this->width;
    }

    function setHeight($height)
    {
        $this->height = (int)$height;
    }

    function getHeight()
    {
        return $this->height;
    }

    function setAdText($ad_text)
    {
        $this->ad_text = PHPWS_Text::parseInput($ad_text);
    }

    function getAdText($format=TRUE)
    {
        if ($format)
        {
            return PHPWS_Text::parseOutput($this->ad_text);
        }

        return $this->ad_text;
    }

    function setUrl($url)
    {
        $this->url = PHPWS_Text::parseInput($url);
    }

    function getUrl()
    {
        return $this->url;
    }

    function getActive()
    {
        $active   = dgettext('ads', 'Active');
        $inactive = dgettext('ads', 'Inactive');

        if (Current_User::allow('ads', 'hide_ads') && ($_REQUEST['module'] == 'ads'))
        {
            $vars['ad_id'] = $this->getId();
            $vars['action'] = 'hideAd';
            return PHPWS_Text::secureLink(($this->active ? $active : $inactive), 'ads', $vars);
        }

        return ($this->active ? $active : $inactive);
    }

    function setApproved($approved)
    {
        $this->approved = (int)$approved;
    }

    function getApproved()
    {
        if ($this->approved == 0)
        {
            return dgettext('ads', 'No');
        }
        else
        {
            $version_db = new PHPWS_DB('ads_version');
            $version_db->addWhere('vr_approved', 0);
            $version_db->addWhere('source_id', $this->id);
            $count = $version_db->count();

            if ($count > 0)
            {
                return dgettext('ads', 'Yes / No');
            }
            else
            {
                return dgettext('ads', 'Yes');
            }
        }
    }

    function getViews()
    {
        $db = new PHPWS_DB('ads_stats');
        $db->addWhere('id', $this->id);
        $db->addColumn('views');
        $result = $db->select('col');
        return $result[0];
    }

    function getHits()
    {
        $db = new PHPWS_DB('ads_stats');
        $db->addWhere('id', $this->id);
        $db->addColumn('hits');
        $result = $db->select('col');
        return $result[0];
    }

    function setCreated($created)
    {
        $this->created = (int)$created;
    }

    function getCreated($format=ADS_DATE_FORMAT)
    {
        return strftime($format, PHPWS_Time::getUserTime($this->created));
    }

    function getCTR($hits, $views)
    {
        if ($views > 0)
        {
            return ($hits*100.0)/$views;
        }
        return 0;
    }

    function incrementStat($stat)
    {
        $db = new PHPWS_DB('ads_stats');
        $db->addWhere('id', $this->id);
        return !PHPWS_Error::logIfError($db->incrementColumn($stat));
    }

    function toggle()
    {
        if (!Current_User::authorized('ads', 'hide_ads'))
        {
            Current_User::disallow();
            return;
        }

        $this->active = ($this->active ? 0 : 1);
        return $this->save(FALSE);
    }

    function save($new_version=TRUE)
    {
        PHPWS_Core::initModClass('version', 'Version.php');

        $db = new PHPWS_DB('ads_campaigns');
        $db->addWhere('ads_campaigns.id', $this->campaign_id);
        $db->addWhere('users.id', 'ads_campaigns.advertiser_id');
        $db->addColumn('users.id');
        $result = $db->select('one');
        if ((PEAR::isError($result) || ($result != Current_User::getId())) && !Current_User::authorized('ads'))
        {
            Current_User::disallow();
            return;
        }

        if (!$this->id)
        {
            /* Create stats entry for this ad. */
            $db2 = new PHPWS_DB('ads_stats');
            $db2->addValue('views', 0);
            $db2->addValue('hits', 0);
            $result = $db2->insert();
            if (PEAR::isError($result))
            {
                return $result;
            }
        }

        if ($this->approved || !$this->id || !$new_version)
        {
            $db = new PHPWS_DB('ads');
            $result = $db->saveObject($this);
            if (PEAR::isError($result))
            {
                return $result;
            }
        }

        if ($new_version)
        {
            $version = new Version('ads');
            $version->setSource($this);
            $version->setApproved($this->approved);
            $version->save();
        }
    }

    /**
     * Removes ads and associated stats from the database.
     *
     * @param  override  Should ONLY be used when user being deleted (see remove_user.php).
     */
    function kill($override=false)
    {
        PHPWS_Core::initModClass('version', 'Version.php');

        if (!$override && !Current_User::authorized('ads'))
        {
            $db = new PHPWS_DB('ads_campaigns');
            $db->addWhere('ads_campaigns.id', $this->campaign_id);
            $db->addWhere('users.id', 'ads_campaigns.advertiser_id');
            $db->addColumn('users.id');
            $result = $db->select('one');
            if (PEAR::isError($result) || ($result != Current_User::getId()))
            {
                Current_User::disallow();
                return;
            }
        }

        Version::flush('ads', $this->id);

        if ($this->ad_type == 0)
        {
            @unlink(PHPWS_HOME_DIR . 'images/ads/' . $this->filename);
        }

        $db = new PHPWS_DB('ads_stats');
        $db->addWhere('id', $this->id);
        PHPWS_Error::logIfError($db->delete());

        $db = new PHPWS_DB('ads');
        $db->addWhere('id', $this->id);
        return !PHPWS_Error::logIfError($db->delete());
    }

    function approval_view()
    {
        $template['TITLE'] = $this->getTitle();
        if (isset($this->filename))
        {
            $template['FILENAME'] = 'images/ads/' . $this->getFilename();
            $template['WIDTH'] = $this->getWidth();
            $template['HEIGHT'] = $this->getHeight();
        }
        $template['AD_TEXT']  = $this->getAdText();
        $template['URL'] = $this->getUrl();

        return PHPWS_Template::process($template, 'ads', 'ad/approval_view.tpl');
    }

    function view()
    {
        if ($this->active && $this->approved)
        {
            if (isset($this->filename))
            {
                $template['FILENAME'] = PHPWS_Text::linkAddress('ads', array('adview'=>$this->id), FALSE, TRUE);
                $template['WIDTH'] = $this->getWidth();
                $template['HEIGHT'] = $this->getHeight();
            }
            else
            {
                /* Only increment the view stat for text ads.  For banners, the view stat
                 * will be incremented when actually displaying the banner.  This is done
                 * to prevent ad blocking software from throwing off our stats. */
                $this->incrementStat('views');
                $template['TITLE'] = $this->getTitle();
            }
            $template['AD_TEXT']  = $this->getAdText();
            $template['URL'] = PHPWS_Text::linkAddress('ads', array('adclick'=>$this->id));

            return PHPWS_Template::process($template, 'ads', 'ad/view.tpl');
        }

        return NULL;
    }

    function getTpl()
    {
        $vars['ad_id'] = $this->getId();

        $vars['action'] = 'viewAd';
        $links[] = PHPWS_Text::secureLink(dgettext('ads', 'View'), 'ads', $vars);

        $vars['action'] = 'editAd';
        $links[] = PHPWS_Text::secureLink(dgettext('ads', 'Edit'), 'ads', $vars);

        $version_db = new PHPWS_DB('ads_version');
        $version_db->addWhere('vr_approved', 1);
        $version_db->addWhere('vr_current', 0);
        $version_db->addWhere('source_id', $this->id);
        $count = $version_db->count();
        if ($count > 0)
        {
            $vars['action'] = 'versions';
            $links[] = PHPWS_Text::secureLink(dgettext('ads', 'Old Versions'), 'ads', $vars);
        }

        $vars['action'] = 'deleteAd';
        $confirm_vars['QUESTION'] = dgettext('ads', 'Are you sure you want to permanently delete this ad?');
        $confirm_vars['ADDRESS'] = PHPWS_Text::linkAddress('ads', $vars, TRUE);
        $confirm_vars['LINK'] = dgettext('ads', 'Delete');
        $links[] = javascript('confirm', $confirm_vars);

        $views = $this->getViews();
        $hits  = $this->getHits();
        $template['ACTION']   = implode(' | ', $links);
        $template['NAME']     = $this->getTitle();
        $template['TYPE']     = $this->getAdType();
        $template['ACTIVE']   = $this->getActive();
        $template['APPROVED'] = $this->getApproved();
        $template['VIEWS']    = number_format($views);
        $template['HITS']     = number_format($hits);
        $template['CTR']      = number_format($this->getCTR($hits, $views), 2);

        return $template;
    }

    /*************************** PUBLIC FUNCTIONS ***************************/

    function click()
    {
        if ($this->active && $this->approved)
        {
            $this->incrementStat('hits');
            PHPWS_Core::reroute('http://' . $this->getUrl());
        }
        include 'config/core/404.html';
        exit();
    }

    function bannerImpression()
    {
        if ($this->active && $this->approved && isset($this->filename))
        {
            $this->incrementStat('views');
            PHPWS_Core::reroute(PHPWS_Core::getHomeHttp() . 'images/ads/' . $this->getFilename());
        }
        exit();
    }
}

?>