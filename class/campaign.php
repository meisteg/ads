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

PHPWS_Core::initModClass('ads', 'ad.php');

class Ads_Campaign
{
    var $id             = 0;
    var $advertiser_id  = 0;
    var $name           = NULL;
    var $priority       = 5;
    var $created        = 0;
    var $_pin_zone      = NULL;


    function Ads_Campaign($id=NULL)
    {
        if (!empty($id))
        {
            $this->setId($id);

            $db = new PHPWS_DB('ads_campaigns');
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

    function setAdvertiserId($advertiser_id)
    {
        $this->advertiser_id = (int)$advertiser_id;
    }

    function getAdvertiserId()
    {
        return $this->advertiser_id;
    }

    function setName($name)
    {
        $this->name = strip_tags($name);
    }

    function getName()
    {
        return $this->name;
    }

    function setPriority($priority)
    {
        $this->priority = (int)$priority;
    }

    function getPriority()
    {
        return $this->priority;
    }

    function setCreated($created)
    {
        $this->created = (int)$created;
    }

    function getCreated($format=ADS_DATE_FORMAT)
    {
        return strftime($format, PHPWS_Time::getUserTime($this->created));
    }

    function setPinZone($zone_id)
    {
        $this->_pin_zone = $zone_id;
    }

    function save()
    {
        if ((Current_User::getId() != $this->advertiser_id) && !Current_User::authorized('ads'))
        {
            Current_User::disallow();
            return;
        }

        $db = new PHPWS_DB('ads_campaigns');
        return $db->saveObject($this);
    }

    function clearPins()
    {
        $db = new PHPWS_DB('ads_campaign_pins');
        $db->addWhere('campaign_id', $this->id);
        $db->delete();
    }

    function clearAds($override)
    {
        $db = new PHPWS_DB('ads');
        $db->addWhere('campaign_id', $this->id);
        $ads = $db->getObjects('Ads_Ad');

        if (!PHPWS_Error::logIfError($ads) && ($ads != NULL))
        {
            foreach ($ads as $ad)
            {
                $ad->kill($override);
            }
        }
    }

    /**
     * Removes campaign and associated ads from the database.
     *
     * @param  override  Should ONLY be used when user being deleted (see remove_user.php).
     */
    function kill($override=false)
    {
        if ((!$override) && (Current_User::getId() != $this->advertiser_id) && !Current_User::authorized('ads'))
        {
            Current_User::disallow();
            return;
        }

        $this->clearPins();
        $this->clearAds($override);
        $db = new PHPWS_DB('ads_campaigns');
        $db->addWhere('id', $this->id);

        return !PHPWS_Error::logIfError($db->delete());
    }

    function getAd(&$ad)
    {
        $zone = new Ads_Zone($this->_pin_zone);

        $db = new PHPWS_DB('ads');
        $db->addWhere('campaign_id', $this->getId());
        $db->addWhere('active', 1);
        $db->addWhere('approved', 1);
        $db->addWhere('ad_type', $zone->ad_type);
        $result = $db->getObjects('Ads_Ad');
        if (PHPWS_Error::logIfError($result))
        {
            return FALSE;
        }

        if (sizeof($result) > 0)
        {
            $rand_key = array_rand($result);
            $selected_ad = $result[$rand_key];
            $ad = $selected_ad->view();
            return TRUE;
        }

        $ad = '(' . $this->getName() . ')';
        return FALSE;
    }

    function view($pin_mode=FALSE)
    {
        $opt = NULL;
        $force_display = FALSE;

        /* if ($this->active) */
        {
            if (Current_User::allow('ads'))
            {
                $force_display = TRUE;

                if (!empty($this->_pin_zone) && $pin_mode)
                {
                    $link['action'] = 'lockCampaign';
                    $link['campaign_id'] = $this->id;
                    $link['zone_id'] = $this->_pin_zone;
                    $img = sprintf('<img src="./images/mod/ads/pin.png" alt="%s" title="%s" />',
                                   dgettext('ads', 'Pin'), dgettext('ads', 'Pin'));
                    $opt = PHPWS_Text::secureLink($img, 'ads', $link);
                }
                elseif (!empty($this->_pin_zone))
                {
                    $vars['action'] = 'removeCampaignPin';
                    $vars['campaign_id'] = $this->id;
                    $vars['zone_id'] = $this->_pin_zone;
                    $js_var['ADDRESS'] = PHPWS_Text::linkAddress('ads', $vars, TRUE);
                    $js_var['QUESTION'] = dgettext('ads', 'Are you sure you want to remove this campaign from this ad zone?');
                    $js_var['LINK'] = sprintf('<img src="./images/mod/ads/remove.png" alt="%s" title="%s" />',
                                              dgettext('ads', 'Remove'), dgettext('ads', 'Remove'));

                    $opt = Layout::getJavascript('confirm', $js_var);
                }
            }

            if ($this->getAd($ad) || $force_display)
            {
                $template = array('AD' => $ad, 'OPT'=> $opt);
                return PHPWS_Template::process($template, 'ads', 'campaign/boxstyles/default.tpl');
            }
        }

        return NULL;
    }

    function getNumberOfAds()
    {
        $db = new PHPWS_DB('ads');
        $db->addWhere('campaign_id', $this->getId());
        $result = $db->count();
        if (PHPWS_Error::logIfError($result))
        {
            return dgettext('ads', 'N/A');
        }

        return $result;
    }

    function isPinned()
    {
        if (!isset($_SESSION['Pinned_Ad_Campaigns']))
        {
            return FALSE;
        }

        return isset($_SESSION['Pinned_Ad_Campaigns'][$this->id]);
    }

    function getTpl()
    {
        $vars['campaign_id'] = $this->getId();

        $vars['action'] = 'manageAds';
        $links[] = PHPWS_Text::secureLink(dgettext('ads', 'Ads'), 'ads', $vars);

        $vars['action'] = 'editCampaign';
        $links[] = PHPWS_Text::secureLink(dgettext('ads', 'Edit'), 'ads', $vars);

        if ($this->isPinned())
        {
            $vars['action'] = 'unpinCampaign';
            $links[] = PHPWS_Text::secureLink(dgettext('ads', 'Unpin'), 'ads', $vars);
        }
        else
        {
            $vars['action'] = 'pinCampaign';
            $links[] = PHPWS_Text::secureLink(dgettext('ads', 'Pin'), 'ads', $vars);
        }

        $vars['action'] = 'deleteCampaign';
        $confirm_vars['QUESTION'] = dgettext('ads', 'Are you sure you want to permanently delete this campaign?');
        $confirm_vars['ADDRESS'] = PHPWS_Text::linkAddress('ads', $vars, TRUE);
        $confirm_vars['LINK'] = dgettext('ads', 'Delete');
        $links[] = javascript('confirm', $confirm_vars);

        $template['ACTION']  = implode(' | ', $links);
        $template['NAME']    = $this->getName();
        $template['ADS']     = $this->getNumberOfAds();
        $template['CREATED'] = $this->getCreated();

        return $template;
    }
}

?>