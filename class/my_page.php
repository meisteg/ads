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

class Ads_My_Page
{
    function main()
    {
        PHPWS_Core::initModClass('ads', 'advertiser.php');
        $db = new PHPWS_DB('ads_advertisers');
        $db->addWhere('user_id', Current_User::getId());
        $db->addColumn('ads_advertisers.*');
        $db->addColumn('demographics.business_name');
        $result = $db->getObjects('Ads_Advertiser');

        if (PHPWS_Error::logIfError($result))
        {
            $content = dgettext('ads', 'Currently unavailable.  Please try again later.');
        }
        else if (empty($result))
        {
            $content = dgettext('ads', 'You are currently not an advertiser on this site.');
        }
        else
        {
            $content = Ads_My_Page::showReport($result[0]->user_id);
            $box['BUSINESS'] = $result[0]->getBusinessName();
        }

        $box['TITLE'] = dgettext('ads', 'Advertiser Report');
        $box['CONTENT'] = &$content;
        return PHPWS_Template::process($box, 'ads', 'my_page.tpl');
    }

    function showReport($advertiser_id)
    {
        $db = new PHPWS_DB('ads_campaigns');
        $db->addWhere('advertiser_id', $advertiser_id);
        $result = $db->getObjects('Ads_Campaign');
        if (PHPWS_Error::logIfError($result))
        {
            $content = dgettext('ads', 'Currently unavailable.  Please try again later.');
        }
        else if (empty($result))
        {
            $content = dgettext('ads', 'You currently do not have any active ad campaigns.');
        }
        else
        {
            PHPWS_Core::initCoreClass('DBPager.php');

            $pageTags['NAME']     = dgettext('ads', 'Ad Name');
            $pageTags['TYPE']     = dgettext('ads', 'Type');
            $pageTags['ACTIVE']   = dgettext('ads', 'Active');
            $pageTags['APPROVED'] = dgettext('ads', 'Approved');
            $pageTags['VIEWS']    = dgettext('ads', 'Views');
            $pageTags['HITS']     = dgettext('ads', 'Hits');
            $pageTags['CTR']      = dgettext('ads', 'CTR');

            foreach($result as $campaign)
            {
                $pageTags['CAMPAIGN'] = $campaign->getName();
                $pager = new DBPager('ads', 'Ads_Ad');
                $pager->setModule('ads');
                $pager->setTemplate('campaign/report.tpl');
                $pager->addToggle(PHPWS_LIST_TOGGLE_CLASS);
                $pager->addPageTags($pageTags);
                $pager->addRowTags('getTpl');
                $pager->setDefaultOrder('title', 'asc');
                if (($num_ads = $campaign->getNumberOfAds()) > 0)
                {
                    $pager->setLimitList(array($num_ads));
                    $pager->setDefaultLimit($num_ads);
                }
                $pager->setEmptyMessage(dgettext('ads', 'No ads found for this campaign.'));
                $pager->addWhere('campaign_id', $campaign->getId());

                $report[] = $pager->get();
            }
            $content = implode('', $report);
        }

        return $content;
    }
}

?>