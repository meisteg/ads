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
 * @version $Id: uninstall.php,v 1.7 2007/05/28 21:21:47 blindman1344 Exp $
 */

function ads_uninstall(&$content)
{
    PHPWS_DB::dropTable('ads');
    PHPWS_DB::dropTable('ads_campaign_pins');
    PHPWS_DB::dropTable('ads_campaigns');
    PHPWS_DB::dropTable('ads_zone_pins');
    PHPWS_DB::dropTable('ads_advertisers');
    PHPWS_DB::dropTable('ads_zones');
    PHPWS_DB::dropTable('ads_stats');

    $content[] = dgettext('ads', 'Ad tables removed.');

    return TRUE;
}

?>