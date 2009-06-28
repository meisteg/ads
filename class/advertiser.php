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

PHPWS_Core::initModClass('demographics', 'Demographics.php');

class Ads_Advertiser extends Demographics_User
{
    /* new demographics field */
    var $business_name   = null;

    /* ads field */
    var $created         = 0;

    /* using a second table with demographics */
    var $_table          = 'ads_advertisers';

    /* advanced join fields */
    var $display_name    = null;


    function Ads_Advertiser($user_id=null)
    {
        if (!empty($user_id))
        {
            $this->user_id = (int)$user_id;
            $this->load();
        }
    }

    function setBusinessName($business_name)
    {
        $this->business_name = PHPWS_Text::parseInput($business_name);
    }

    function getBusinessName()
    {
        return PHPWS_Text::parseOutput($this->business_name);
    }

    function setCreated($created)
    {
        $this->created = (int)$created;
    }

    function getCreated($format=ADS_DATE_FORMAT)
    {
        return strftime($format, PHPWS_Time::getUserTime($this->created));
    }

    function getDisplayName()
    {
        if (empty($this->display_name))
        {
            $db = new PHPWS_DB('users');
            $db->addWhere('users.id', $this->user_id);
            $db->addColumn('username');
            $this->display_name = $db->select('one');
        }

        return $this->display_name;
    }

    /**
     * Removes advertiser and associated campaigns from the database.
     *
     * @param  override  Should ONLY be used when user being deleted (see remove_user.php).
     */
    function kill($override=false)
    {
        if (!Current_User::authorized('ads', 'delete_advertisers') && !$override)
        {
            Current_User::disallow();
            return;
        }

        $db = new PHPWS_DB('ads_campaigns');
        $db->addWhere('advertiser_id', $this->user_id);
        $campaigns = $db->getObjects('Ads_Campaign');

        if (!PHPWS_Error::logIfError($campaigns) && ($campaigns != NULL))
        {
            foreach ($campaigns as $campaign)
            {
                $campaign->kill($override);
            }
        }

        return $this->delete();
    }

    function getTpl()
    {
        $vars['advertiser_id'] = $this->user_id;

        $vars['action'] = 'manageCampaigns';
        $links[] = PHPWS_Text::secureLink(dgettext('ads', 'Campaigns'), 'ads', $vars);

        if (Current_User::allow('ads', 'delete_advertisers'))
        {
            $vars['action'] = 'deleteAdvertiser';
            $confirm_vars['QUESTION'] = dgettext('ads', 'Are you sure you want to permanently delete this advertiser?');
            $confirm_vars['ADDRESS'] = PHPWS_Text::linkAddress('ads', $vars, TRUE);
            $confirm_vars['LINK'] = dgettext('ads', 'Delete');
            $links[] = javascript('confirm', $confirm_vars);
        }

        $template['ACTION'] = implode(' | ', $links);
        $template['DISPLAY_NAME'] = $this->getDisplayName();
        $template['BUSINESS_NAME'] = $this->getBusinessName();
        $template['CREATED'] = $this->getCreated();

        return $template;
    }
}

?>