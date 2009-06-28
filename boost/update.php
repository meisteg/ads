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

function ads_update(&$content, $currentVersion)
{
    switch ($currentVersion)
    {
        case version_compare($currentVersion, '1.1.0', '<'):
            $content[] = '- Updated to new translation functions.';

        case version_compare($currentVersion, '1.2.0', '<'):
            $db = new PHPWS_DB('ads_advertisers');
            PHPWS_Error::logIfError($db->dropTableColumn('username'));

            $files = array('templates/advertiser/list.tpl');
            ads_update_files($files, $content);

            $content[] = '- Drop username column from advertisers table.';
            $content[] = '- Advertisers deleted when associated site user is deleted.';
            $content[] = '- Switched to PHPWS_LIST_TOGGLE_CLASS define for DBPager.';
            $content[] = '- Added empty messages to DBPager.';
            $content[] = '- Corrected a few phrases that were not being translated.';

        case version_compare($currentVersion, '1.3.0', '<'):
            /* Step 1: Update advertiser ids in campaign table to user ids. */
            $db = new PHPWS_DB('ads_campaigns');
            PHPWS_Error::logIfError($db->query('UPDATE ads_campaigns,ads_advertisers
                                                SET ads_campaigns.advertiser_id = ads_advertisers.user_id
                                                WHERE ( ads_campaigns.advertiser_id = ads_advertisers.id )'));

            /* Step 2: Read out current contents of advertisers table. */
            $db2 = new PHPWS_DB('ads_advertisers');
            $results = $db2->select();
            PHPWS_Error::logIfError($results);

            /* Step 3: Change advertisers table to new structure. */
            PHPWS_Error::logIfError($db2->dropTableColumn('id'));
            PHPWS_Error::logIfError($db2->dropTableColumn('business'));
            PHPWS_Error::logIfError($db2->createTableIndex('user_id', 'userid_idx', true));

            /* Step 4: Update demographics table with advertiser business name. */
            if (!empty($results))
            {
                PHPWS_Core::initModClass('ads', 'advertiser.php');
                Demographics::register('ads');

                foreach ($results as $row)
                {
                    $advertiser = new Ads_Advertiser($row['user_id']);
                    $advertiser->setBusinessName($row['business']);
                    PHPWS_Error::logIfError($advertiser->save());
                }
            }

            /* Step 5: Update the templates. */
            $files = array('templates/advertiser/list.tpl',
                           'templates/advertiser/select.tpl',
                           'templates/advertiser/add.tpl');
            ads_update_files($files, $content);

            /* Done */
            $content[] = '- Now use demographics module to store the business name of advertisers.';
            $content[] = '- Removed call to help module which wasn\'t working anyway.';
    }

    return TRUE;
}

function ads_update_files($files, &$content)
{
    if (PHPWS_Boost::updateFiles($files, 'ads'))
    {
        $content[] = '- Updated the following files:';
    }
    else
    {
        $content[] = '- Unable to update the following files:';
    }

    foreach ($files as $file)
    {
        $content[] = '--- ' . $file;
    }
}

?>