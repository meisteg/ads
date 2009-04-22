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
 * @version $Id: key.php,v 1.3 2008/08/16 20:19:18 blindman1344 Exp $
 */

function ads_unregister_key(&$key)
{
    if (empty($key) || empty($key->id))
    {
        return FALSE;
    }

    $db = new PHPWS_DB('ads_zone_pins');
    $db->addWhere('key_id', $key->id);
    PHPWS_Error::logIfError($db->delete());
}

?>