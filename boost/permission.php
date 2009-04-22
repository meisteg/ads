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
 * @version $Id: permission.php,v 1.6 2007/05/28 21:21:47 blindman1344 Exp $
 */

$use_permissions = TRUE;

$permissions['edit_zones']         = dgettext('ads', 'Edit zones');
$permissions['hide_zones']         = dgettext('ads', 'Hide zones');
$permissions['delete_zones']       = dgettext('ads', 'Delete zones');

$permissions['edit_advertisers']   = dgettext('ads', 'Edit advertisers');
$permissions['delete_advertisers'] = dgettext('ads', 'Delete advertisers');

$permissions['hide_ads']           = dgettext('ads', 'Hide ads');
$permissions['approve_ads']        = dgettext('ads', 'Approve ads');

$item_permissions = TRUE;

?>