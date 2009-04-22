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
 * @version $Id: parse.php,v 1.2 2006/05/14 20:53:30 blindman1344 Exp $
 */

function ads_zone($zone_id)
{
    $zone = new Ads_Zone((int)$zone_id);
    if (empty($zone->id))
    {
        return NULL;
    }
    $template['ZONE'] = $zone->view(FALSE, FALSE);

    if (empty($template['ZONE']))
    {
        return NULL;
    }
    else
    {
        return PHPWS_Template::process($template, 'ads', 'zone/embedded.tpl');
    }
}

?>