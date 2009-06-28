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

PHPWS_Core::initModClass('ads', 'zone.php');

class Ads_Runtime {

    function show()
    {
        Ads_Runtime::showAllZones();

        $key = Key::getCurrent();

        if (empty($key) || $key->isDummy(true))
        {
            return;
        }
        Ads_Runtime::showZones($key);

        if (isset($_SESSION['Pinned_Ad_Zones']))
        {
            Ads_Runtime::viewPinnedZones($key);
        }

    }

    function showAllZones()
    {
        $key = new Key;
        $key->id = -1;
        Ads_Runtime::showZones($key);
    }

    function viewPinnedZones($key)
    {
        if (!isset($_SESSION['Pinned_Ad_Zones']))
        {
            return FALSE;
        }

        $zone_list = &$_SESSION['Pinned_Ad_Zones'];
        if (empty($zone_list))
        {
            return NULL;
        }

        foreach ($zone_list as $zone_id => $zone) {
            if (isset($GLOBALS['Current_Zones'][$zone_id]))
            {
                continue;
            }

            $zone->setPinKey($key);
            $content[] = $zone->view(TRUE);
        }

        if (empty($content))
        {
            return;
        }

        $complete = implode('', $content);
        Layout::add($complete, 'ads', 'Zone_List');
    }

    function showZones($key)
    {
        $db = new PHPWS_DB('ads_zones');
        $db->addWhere('ads_zone_pins.key_id', $key->id);
        $db->addWhere('id', 'ads_zone_pins.zone_id');
        Key::restrictView($db, 'ads');
        $result = $db->getObjects('Ads_Zone');

        if (PEAR::isError($result))
        {
            PHPWS_Error::log($result);
            return NULL;
        }

        if (empty($result))
        {
            return NULL;
        }

        foreach ($result as $zone)
        {
            $zone->setPinKey($key);
            Layout::add($zone->view(), 'ads', $zone->getLayoutContentVar());
            $GLOBALS['Current_Zones'][$zone->id] = TRUE;
        }
    }
}

?>