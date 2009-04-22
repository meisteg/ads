<?php
/**
 * Ads for phpWebSite
 *
 * See docs/CREDITS for copyright information
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
 * @author Greg Meiste <blindman1344 at users dot sourceforge dot net>
 * @version $Id: zone.php,v 1.9 2007/05/28 21:21:49 blindman1344 Exp $
 */

PHPWS_Core::initModClass('ads', 'campaign.php');

class Ads_Zone
{
    var $id          = 0;
    var $key_id      = 0;
    var $title       = NULL;
    var $description = NULL;
    var $ad_type     = 0;
    var $max_num_ads = 1;
    var $active      = 1;
    var $_pin_key    = NULL;


    function Ads_Zone($id=NULL)
    {
        if (empty($id))
        {
            return;
        }
        $this->setId($id);

        $db = new PHPWS_DB('ads_zones');
        $db->loadObject($this);
    }

    function setId($id)
    {
        $this->id = (int)$id;
    }

    function getId()
    {
        return $this->id;
    }

    function setTitle($title)
    {
        $this->title = strip_tags($title);
    }

    function getTitle()
    {
        return $this->title;
    }

    function getLayoutContentVar()
    {
        return 'ad_zone_' . $this->id;
    }

    function setDescription($description)
    {
        $this->description = PHPWS_Text::parseInput($description);
    }

    function getDescription($format=TRUE)
    {
        if ($format)
        {
            return PHPWS_Text::parseOutput($this->description);
        }
        else
        {
            return $this->description;
        }
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

    function setMaxNumAds($max_num_ads)
    {
        $this->max_num_ads = (int)$max_num_ads;
    }

    function getMaxNumAds()
    {
        return $this->max_num_ads;
    }

    function setPinKey($key)
    {
        $this->_pin_key = $key;
    }

    function getKey()
    {
        $key = new Key($this->key_id);
        return $key;
    }

    function getTag()
    {
        return '[ads:zone:' . $this->id . ']';
    }

    function getActive()
    {
        $vars['zone_id'] = $this->getId();

        if ($this->active)
        {
            if (Current_User::allow('ads', 'hide_zones', $this->id))
            {
                $vars['action'] = 'hideZone';
                return PHPWS_Text::secureLink(dgettext('ads', 'Active'), 'ads', $vars);
            }
            else
            {
                return dgettext('ads', 'Active');
            }
        }
        else
        {
            if (Current_User::allow('ads', 'hide_zones', $this->id))
            {
                $vars['action'] = 'hideZone';
                return PHPWS_Text::secureLink(dgettext('ads', 'Inactive'), 'ads', $vars);
            }
            else
            {
                return dgettext('ads', 'Inactive');
            }
        }
    }

    function save($save_key=TRUE)
    {
        if ($this->getId())
        {
            if (!Current_User::authorized('ads', 'edit_zones', $this->id))
            {
                Current_User::disallow();
                return;
            }
        }
        else
        {
            if (!Current_User::authorized('ads', 'edit_zones'))
            {
                Current_User::disallow();
                return;
            }
        }

        $db = new PHPWS_DB('ads_zones');
        $result = $db->saveObject($this);
        if (PEAR::isError($result))
        {
            return $result;
        }

        if ($save_key)
        {
            return $this->saveKey();
        }
    }

    function saveKey()
    {
        if (empty($this->key_id))
        {
            $key = new Key;
            $key->module = 'ads';
            $key->item_name = 'zone';
            $key->item_id = $this->id;
        }
        else
        {
            $key = new Key($this->key_id);
        }

        $key->edit_permission = 'edit_zones';
        $key->title = $this->title;
        $result = $key->save();
        if (PEAR::isError($result))
        {
            return $result;
        }

        if (empty($this->key_id))
        {
            $this->key_id = $key->id;
            $this->save(FALSE);
        }
    }

    function toggle()
    {
        if (!Current_User::authorized('ads', 'hide_zones', $this->id))
        {
            Current_User::disallow();
            return;
        }

        if ($this->active)
        {
            $this->active = 0;
        }
        else
        {
            $this->active = 1;
        }

        return $this->save(FALSE);
    }

    function clearPins()
    {
        $db = new PHPWS_DB('ads_zone_pins');
        $db->addWhere('zone_id', $this->id);
        $db->delete();

        $db2 = new PHPWS_DB('ads_campaign_pins');
        $db2->addWhere('zone_id', $this->id);
        $db2->delete();
    }

    function kill()
    {
        if (!Current_User::authorized('ads', 'delete_zones', $this->id))
        {
            Current_User::disallow();
            return;
        }

        $this->clearPins();
        $db = new PHPWS_DB('ads_zones');
        $db->addWhere('id', $this->id);

        $result = $db->delete();
        if (PEAR::isError($result))
        {
            PHPWS_Error::log($result);
        }

        $key = new Key($this->key_id);
        $result = $key->delete();
        if (PEAR::isError($result))
        {
            PHPWS_Error::log($result);
        }
    }

    function getPinnedCampaigns(&$adArray)
    {
        if (!isset($_SESSION['Pinned_Ad_Campaigns']))
        {
            return;
        }

        $campaign_list = &$_SESSION['Pinned_Ad_Campaigns'];
        if (empty($campaign_list))
        {
            return;
        }

        foreach ($campaign_list as $campaign_id => $campaign)
        {
            if (isset($GLOBALS['Current_Campaigns_' . $this->getId()][$campaign_id]))
            {
                continue;
            }

            $campaign->setPinZone($this->getId());
            $adArray[] = $campaign->view(TRUE);
        }
    }

    function getAds(&$ads)
    {
        $GLOBALS['Current_Campaigns_' . $this->getId()] = array();
        $adArray = array();
        $priorityArray = array();

        $db = new PHPWS_DB('ads_campaigns');
        $db->addWhere('ads_campaign_pins.zone_id', $this->getId());
        $db->addWhere('id', 'ads_campaign_pins.campaign_id');
        $result = $db->getObjects('Ads_Campaign');
        if (PEAR::isError($result))
        {
            PHPWS_Error::log($result);
        }
        else if ($result != NULL)
        {
            foreach ($result as $campaign)
            {
                $GLOBALS['Current_Campaigns_' . $this->getId()][$campaign->id] = $campaign;

                for ($i=0; $i < $campaign->getPriority(); $i++)
                {
                    $priorityArray[] = $campaign->id;
                }
            }
        }

        $this->getPinnedCampaigns($adArray);

        while ((sizeof($adArray) < $this->getMaxNumAds()) && (sizeof($GLOBALS['Current_Campaigns_' . $this->getId()]) > 0))
        {
            $rand_key = array_rand($priorityArray);
            if (isset($GLOBALS['Current_Campaigns_' . $this->getId()][$priorityArray[$rand_key]]))
            {
                $campaign = $GLOBALS['Current_Campaigns_' . $this->getId()][$priorityArray[$rand_key]];
                unset($GLOBALS['Current_Campaigns_' . $this->getId()][$priorityArray[$rand_key]]);
                $campaign->setPinZone($this->getId());
                $campaignView = $campaign->view();
                if ($campaignView != NULL)
                {
                    $adArray[] = $campaignView;
                }
            }

            unset($priorityArray[$rand_key]);
        }

        if (empty($adArray))
        {
            $ads = dgettext('ads', 'No ads to display');
        }
        else
        {
            $ads = implode('', $adArray);
            return TRUE;
        }

        return FALSE;
    }

    function view($pin_mode=FALSE, $admin_icon=TRUE)
    {
        $opt = NULL;
        $force_display = FALSE;

        if ($this->active && ($this->getTitle() != NULL))
        {
            if (Current_User::allow('ads'))
            {
                $force_display = TRUE;

                if (!empty($this->_pin_key) && $pin_mode)
                {
                    $link['action'] = 'lockZone';
                    $link['zone_id'] = $this->id;
                    $link['key_id'] = $this->_pin_key->id;
                    $img = sprintf('<img src="./images/mod/ads/pin.png" alt="%s" title="%s" />',
                                   dgettext('ads', 'Pin'), dgettext('ads', 'Pin'));
                    $opt = PHPWS_Text::secureLink($img, 'ads', $link);
                }
                elseif (!empty($this->_pin_key) && $admin_icon)
                {
                    $vars['action'] = 'removeZonePin';
                    $vars['zone_id'] = $this->id;
                    $vars['key_id'] = $this->_pin_key->id;
                    $js_var['ADDRESS'] = PHPWS_Text::linkAddress('ads', $vars, TRUE);
                    $js_var['QUESTION'] = dgettext('ads', 'Are you sure you want to remove this ad zone from this page?');
                    $js_var['LINK'] = sprintf('<img src="./images/mod/ads/remove.png" alt="%s" title="%s" />',
                                              dgettext('ads', 'Remove'), dgettext('ads', 'Remove'));

                    $opt = Layout::getJavascript('confirm', $js_var);
                }
            }

            if ($this->getAds($ads) || $force_display)
            {
                $template = array('TITLE' => $this->getTitle(), 'CONTENT' => $ads, 'OPT'=> $opt);
                return PHPWS_Template::process($template, 'ads', 'zone/boxstyles/default.tpl');
            }
        }

        return NULL;
    }

    function isPinned()
    {
        if (!isset($_SESSION['Pinned_Ad_Zones']))
        {
            return FALSE;
        }

        return isset($_SESSION['Pinned_Ad_Zones'][$this->id]);
    }

    function allPinned()
    {
        static $all_pinned = null;

        if (empty($all_pinned))
        {
            $db = new PHPWS_DB('ads_zone_pins');
            $db->addWhere('key_id', -1);
            $db->addColumn('zone_id');
            $result = $db->select('col');
            if (PEAR::isError($result))
            {
                PHPWS_Error::log($result);
                return false;
            }
            if ($result)
            {
                $all_pinned = $result;
            }
            else
            {
                $all_pinned = true;
            }
        }

        if (is_array($all_pinned))
        {
            return in_array($this->id, $all_pinned);
        }
        else
        {
            return false;
        }

    }

    function getTpl()
    {
        $vars['zone_id'] = $this->getId();

        if (Current_User::allow('ads', 'edit_zones', $this->id))
        {
            $vars['action'] = 'editZone';
            $links[] = PHPWS_Text::secureLink(dgettext('ads', 'Edit'), 'ads', $vars);
        }

        if ($this->isPinned())
        {
            $vars['action'] = 'unpinZone';
            $links[] = PHPWS_Text::secureLink(dgettext('ads', 'Unpin'), 'ads', $vars);
        }
        else
        {
            if ($this->allPinned())
            {
                $vars['action'] = 'removeZonePin';
                $links[] = PHPWS_Text::secureLink(dgettext('ads', 'Unpin all'), 'ads', $vars);
            }
            else
            {
                $vars['action'] = 'pinZone';
                $links[] = PHPWS_Text::secureLink(dgettext('ads', 'Pin'), 'ads', $vars);
                $vars['action'] = 'pinZoneAll';
                $links[] = PHPWS_Text::secureLink(dgettext('ads', 'Pin all'), 'ads', $vars);
            }
        }

        if (Current_User::isUnrestricted('ads'))
        {
            $links[] = Current_User::popupPermission($this->key_id);
        }

        $vars['action'] = 'copyZone';
        $links[] = PHPWS_Text::secureLink(dgettext('ads', 'Copy'), 'ads', $vars);

        if (Current_User::allow('ads', 'delete_zones'))
        {
            $vars['action'] = 'deleteZone';
            $confirm_vars['QUESTION'] = dgettext('ads', 'Are you sure you want to permanently delete this zone?');
            $confirm_vars['ADDRESS'] = PHPWS_Text::linkAddress('ads', $vars, TRUE);
            $confirm_vars['LINK'] = dgettext('ads', 'Delete');
            $links[] = javascript('confirm', $confirm_vars);
        }

        $template['ACTION'] = implode(' | ', $links);
        $template['DESCRIPTION'] = $this->getDescription();
        $template['AD_TYPE'] = $this->getAdType();
        $template['ACTIVE'] = $this->getActive();

        return $template;
    }
}

?>