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

class Ads_Admin
{
    function action()
    {
        if (!Current_User::allow('ads'))
        {
            Current_User::disallow();
            return;
        }

        $panel = & Ads_Admin::cpanel();
        if (isset($_REQUEST['action']))
        {
            $action = $_REQUEST['action'];
        }
        else
        {
            $tab = $panel->getCurrentTab();
            if (empty($tab))
            {
                $action = 'manageZones';
            }
            else
            {
                $action = &$tab;
            }
        }

        $panel->setContent(Ads_Admin::route($action, $panel));
        Layout::add(PHPWS_ControlPanel::display($panel->display()));
    }

    function &cpanel()
    {
        PHPWS_Core::initModClass('controlpanel', 'Panel.php');
        PHPWS_Core::initModClass('version', 'Version.php');

        $linkBase = 'index.php?module=ads';
        if (Current_User::allow('ads', 'edit_zones'))
        {
            $tabs['newZone'] = array ('title'=>dgettext('ads', 'New Zone'), 'link'=> $linkBase);
        }
        $tabs['manageZones'] = array ('title'=>dgettext('ads', 'Manage Zones'), 'link'=> $linkBase);
        if (Current_User::allow('ads', 'edit_advertisers'))
        {
            $tabs['newAdvertiser'] = array ('title'=>dgettext('ads', 'New Advertiser'), 'link'=> $linkBase);
        }
        $tabs['manageAdvertisers'] = array ('title'=>dgettext('ads', 'Manage Advertisers'), 'link'=> $linkBase);
        if (Current_User::allow('ads', 'approve_ads'))
        {
            $version = new Version('ads');
            $unapproved = $version->countUnapproved();
            $tabs['approval'] = array ('title'=>sprintf(dgettext('ads', 'Approval (%s)'), $unapproved), 'link'=> $linkBase);
        }

        $panel = new PHPWS_Panel('ads');
        $panel->enableSecure();
        $panel->quickSetTabs($tabs);

        $panel->setModule('ads');
        return $panel;
    }

    function route($action, &$panel)
    {
        $title   = NULL;
        $content = NULL;
        $message = Ads_Admin::getMessage();
        PHPWS_Core::initModClass('ads', 'advertiser.php');

        $zone = new Ads_Zone(isset($_REQUEST['zone_id']) ? $_REQUEST['zone_id'] : NULL);
        $advertiser = new Ads_Advertiser(isset($_REQUEST['advertiser_id']) ? $_REQUEST['advertiser_id'] : NULL);

        if (isset($_REQUEST['campaign_id']))
        {
            $campaign = new Ads_Campaign($_REQUEST['campaign_id']);
        }
        else
        {
            $campaign = new Ads_Campaign();
            if (isset($_REQUEST['advertiser_id']))
            {
                $campaign->setAdvertiserId($_REQUEST['advertiser_id']);
            }
        }

        if (isset($_REQUEST['ad_id']))
        {
            $ad = new Ads_Ad($_REQUEST['ad_id']);
        }
        else
        {
            $ad = new Ads_Ad();
            if (isset($_REQUEST['campaign_id']))
            {
                $ad->setCampaignId($_REQUEST['campaign_id']);
            }
            if (isset($_REQUEST['ad_type']))
            {
                $ad->setAdType($_REQUEST['ad_type']);
            }
        }

        switch ($action)
        {
            /******************** BEGIN ZONE CASES ********************/
            case 'newZone':
                $title = dgettext('ads', 'New Zone');
                $content = Ads_Admin::editZone($zone);
                break;

            case 'deleteZone':
                $zone->kill();
                Ads_Admin::sendMessage(dgettext('ads', 'Zone deleted'), 'manageZones');
                break;

            case 'editZone':
                $title = dgettext('ads', 'Edit Zone');
                $content = Ads_Admin::editZone($zone);
                break;

            case 'hideZone':
                if (PHPWS_Error::logIfError($zone->toggle()))
                {
                    Ads_Admin::sendMessage(dgettext('ads', 'Zone activation could not be changed'), 'manageZones');
                }
                Ads_Admin::sendMessage(dgettext('ads', 'Zone activation changed'), 'manageZones');
                break;

            case 'pinZone':
                $_SESSION['Pinned_Ad_Zones'][$zone->getId()] = $zone;
                Ads_Admin::sendMessage(dgettext('ads', 'Zone pinned'), 'manageZones');
                break;

            case 'pinZoneAll':
                Ads_Admin::pinZoneAll($zone->getId());
                Ads_Admin::sendMessage(dgettext('ads', 'Zone pinned'), 'manageZones');
                break;

            case 'unpinZone':
                unset($_SESSION['Pinned_Ad_Zones'][$zone->getId()]);
                Ads_Admin::sendMessage(dgettext('ads', 'Zone unpinned'), 'manageZones');
                break;

            case 'removeZonePin':
                Ads_Admin::removeZonePin();
                PHPWS_Core::goBack();
                break;

            case 'copyZone':
                Clipboard::copy($zone->getTitle(), $zone->getTag());
                PHPWS_Core::goBack();
                break;

            case 'postZone':
                $result = Ads_Admin::postZone($zone);
                if (is_array($result))
                {
                    $title = dgettext('ads', 'Edit Zone');
                    $content = Ads_Admin::editZone($zone, FALSE, $result);
                }
                else
                {
                    if (PHPWS_Error::logIfError($zone->save()))
                    {
                        Ads_Admin::sendMessage(dgettext('ads', 'Zone could not be saved'), 'manageZones');
                    }
                    Ads_Admin::sendMessage(dgettext('ads', 'Zone saved'), 'manageZones');
                }
                break;

            case 'postJSZone':
                $result = Ads_Admin::postZone($zone);
                if (is_array($result))
                {
                    $template['TITLE'] = dgettext('ads', 'Edit Zone');
                    $template['CONTENT'] = Ads_Admin::editZone($zone, TRUE, $result);
                    $content = PHPWS_Template::process($template, 'ads', 'admin.tpl');
                    Layout::nakedDisplay($content);
                }
                else
                {
                    if (!PHPWS_Error::logIfError($zone->save()) && isset($_REQUEST['key_id']))
                    {
                        Ads_Admin::lockZone($zone->id, $_REQUEST['key_id']);
                    }
                    javascript('close_refresh');
                }
                break;

            case 'lockZone':
                PHPWS_Error::logIfError(Ads_Admin::lockZone($_GET['zone_id'], $_GET['key_id']));
                PHPWS_Core::goBack();
                break;

            case 'manageZones':
                /* Need to set tab in case we got here from another action. */
                $panel->setCurrentTab('manageZones');
                $title = dgettext('ads', 'Manage Zones');
                $content = Ads_Admin::listZones();
                break;

            case 'editJSZone':
                $template['TITLE'] = dgettext('ads', 'New Zone');
                $template['CONTENT'] = Ads_Admin::editZone($zone, TRUE);
                $content = PHPWS_Template::process($template, 'ads', 'admin.tpl');
                Layout::nakedDisplay($content);
                break;

            /********************* END ZONE CASES *********************/
            /***************** BEGIN ADVERTISER CASES *****************/

            case 'newAdvertiser':
                $title = dgettext('ads', 'New Advertiser - Step 1');
                $content = Ads_Admin::selectAdvertiser();
                break;

            case 'addAdvertiser':
                $title = dgettext('ads', 'New Advertiser - Step 2');
                $content = Ads_Admin::addAdvertiser($advertiser);
                break;

            case 'postAdvertiser':
                if (Ads_Admin::postAdvertiser($advertiser))
                {
                    if (PHPWS_Error::logIfError($advertiser->save()))
                    {
                        Ads_Admin::sendMessage(dgettext('ads', 'Advertiser could not be saved'), 'manageAdvertisers');
                    }
                    Ads_Admin::sendMessage(dgettext('ads', 'Advertiser saved'), 'manageAdvertisers');
                }
                Ads_Admin::sendMessage(dgettext('ads', 'Business name must be specified'), 'manageAdvertisers');
                break;

            case 'deleteAdvertiser':
                if ($advertiser->kill())
                {
                    Ads_Admin::sendMessage(dgettext('ads', 'Advertiser deleted'), 'manageAdvertisers');
                }
                Ads_Admin::sendMessage(dgettext('ads', 'Advertiser could not be deleted'), 'manageAdvertisers');
                break;

            case 'manageAdvertisers':
                /* Need to set tab in case we got here from another action. */
                $panel->setCurrentTab('manageAdvertisers');
                $title = dgettext('ads', 'Manage Advertisers');
                $content = Ads_Admin::listAdvertisers();
                break;

            /****************** END ADVERTISER CASES ******************/
            /****************** BEGIN CAMPAIGN CASES ******************/

            case 'newCampaign':
                $title = dgettext('ads', 'New Campaign');
                $content = Ads_Admin::editCampaign($campaign);
                break;

            case 'editCampaign':
                $title = dgettext('ads', 'Edit Campaign');
                $content = Ads_Admin::editCampaign($campaign);
                break;

            case 'pinCampaign':
                $_SESSION['Pinned_Ad_Campaigns'][$campaign->getId()] = $campaign;
                Ads_Admin::sendMessage(dgettext('ads', 'Campaign pinned'),
                                       array('action'=>'manageCampaigns',
                                       'advertiser_id'=>$campaign->getAdvertiserId()));
                break;

            case 'unpinCampaign':
                unset($_SESSION['Pinned_Ad_Campaigns'][$campaign->getId()]);
                Ads_Admin::sendMessage(dgettext('ads', 'Campaign unpinned'),
                                       array('action'=>'manageCampaigns',
                                       'advertiser_id'=>$campaign->getAdvertiserId()));
                break;

            case 'postCampaign':
                $result = Ads_Admin::postCampaign($campaign);
                if (is_array($result))
                {
                    $title = dgettext('ads', 'Edit Campaign');
                    $content = Ads_Admin::editCampaign($campaign, $result);
                }
                else
                {
                    if (PHPWS_Error::logIfError($campaign->save()))
                    {
                        Ads_Admin::sendMessage(dgettext('ads', 'Campaign could not be saved'),
                                               array('action'=>'manageCampaigns',
                                               'advertiser_id'=>$campaign->getAdvertiserId()));
                    }
                    Ads_Admin::sendMessage(dgettext('ads', 'Campaign saved'), array('action'=>'manageCampaigns',
                                           'advertiser_id'=>$campaign->getAdvertiserId()));
                }
                break;

            case 'deleteCampaign':
                if ($campaign->kill())
                {
                    Ads_Admin::sendMessage(dgettext('ads', 'Campaign deleted'),
                                           array('action'=>'manageCampaigns',
                                           'advertiser_id'=>$campaign->getAdvertiserId()));
                }
                Ads_Admin::sendMessage(dgettext('ads', 'Campaign could not be deleted'), array('action'=>'manageCampaigns',
                                       'advertiser_id'=>$campaign->getAdvertiserId()));
                break;

            case 'lockCampaign':
                PHPWS_Error::logIfError(Ads_Admin::lockCampaign($_GET['campaign_id'], $_GET['zone_id']));
                PHPWS_Core::goBack();
                break;

            case 'removeCampaignPin':
                Ads_Admin::removeCampaignPin();
                PHPWS_Core::goBack();
                break;

            case 'manageCampaigns':
                $title = sprintf(dgettext('ads', 'Manage Campaigns: %s'), $advertiser->getDisplayName());
                $content = Ads_Admin::listCampaigns($_GET['advertiser_id']);
                break;

            /******************* END CAMPAIGN CASES *******************/
            /********************* BEGIN AD CASES *********************/

            case 'newAd':
                if ($ad->ad_type == 0)
                {
                    $title = dgettext('ads', 'New Banner Ad');
                }
                else
                {
                    $title = dgettext('ads', 'New Text Ad');
                }
                $content = Ads_Admin::editAd($ad);
                break;

            case 'editUnapprovedAd':
                $approvalList = true;
                $version = new Version('ads', $_REQUEST['version_id']);
                $version->loadObject($ad);
                /* fall through */

            case 'editAd':
                if ($ad->ad_type == 0)
                {
                    $title = dgettext('ads', 'Edit Banner Ad');
                }
                else
                {
                    $title = dgettext('ads', 'Edit Text Ad');
                }
                $content = Ads_Admin::editAd($ad, NULL, isset($approvalList));
                break;

            case 'postAd':
                $result = Ads_Admin::postAd($ad);
                if (is_array($result))
                {
                    $title = dgettext('ads', 'Edit Ad');
                    $content = Ads_Admin::editAd($ad, $result, isset($_POST['approvalList']));
                }
                else
                {
                    $newAction = isset($_POST['approvalList']) ? 'approval' : 'manageAds';

                    if (PHPWS_Error::logIfError($ad->save()))
                    {
                        Ads_Admin::sendMessage(dgettext('ads', 'Ad could not be saved'), array('action'=>$newAction,
                                               'campaign_id'=>$ad->getCampaignId()));
                    }
                    Ads_Admin::sendMessage(dgettext('ads', 'Ad saved'), array('action'=>$newAction,
                                           'campaign_id'=>$ad->getCampaignId()));
                }
                break;

            case 'hideAd':
                if (PHPWS_Error::logIfError($ad->toggle()))
                {
                    Ads_Admin::sendMessage(dgettext('ads', 'Ad activation could not be changed'),
                                           array('action'=>'manageAds', 'campaign_id'=>$ad->getCampaignId()));
                }
                Ads_Admin::sendMessage(dgettext('ads', 'Ad activation changed'), array('action'=>'manageAds',
                                       'campaign_id'=>$ad->getCampaignId()));
                break;

            case 'deleteAd':
                if ($ad->kill())
                {
                    Ads_Admin::sendMessage(dgettext('ads', 'Ad deleted'),
                                           array('action'=>'manageAds', 'campaign_id'=>$ad->getCampaignId()));
                }
                Ads_Admin::sendMessage(dgettext('ads', 'Ad could not be deleted'),
                                           array('action'=>'manageAds', 'campaign_id'=>$ad->getCampaignId()));
                break;

            case 'manageAds':
                $title = sprintf(dgettext('ads', 'Manage Ads: %s'), $campaign->getName());
                $content = Ads_Admin::listAds($_GET['campaign_id']);
                break;

            case 'approval':
                if (!Current_User::allow('ads', 'approve_ads'))
                {
                    Current_User::disallow();
                    return;
                }

                $title = dgettext('ads', 'Ads Awaiting Approval');
                $approval = new Version_Approval('ads', 'ads', 'Ads_Ad', 'approval_view');

                $vars['action'] = 'editUnapprovedAd';
                $approval->setEditUrl(PHPWS_Text::linkAddress('ads', $vars, TRUE));

                $vars['action'] = 'approveAd';
                $approval->setApproveUrl(PHPWS_Text::linkAddress('ads', $vars, TRUE));

                $vars['action'] = 'disapproveAd';
                $approval->setDisapproveUrl(PHPWS_Text::linkAddress('ads', $vars, TRUE));

                $content = $approval->getList();
                break;

            case 'disapproveAd':
                if (!Current_User::allow('ads', 'approve_ads'))
                {
                    Current_User::disallow();
                    return;
                }

                $version = new Version('ads', $_REQUEST['version_id']);

                if ($version->vr_number == 1)
                {
                    /* Can't rely on version to kill ad... there are other things to be done */
                    $version->loadObject($ad);
                    $ad->kill();
                }

                if (PHPWS_Error::logIfError($version->delete()))
                {
                    Ads_Admin::sendMessage(dgettext('ads', 'A problem occurred when trying to disapprove this ad.'),
                                           'approval');
                }
                Ads_Admin::sendMessage(dgettext('ads', 'Ad disapproved.'), 'approval');
                break;

            case 'approveAd':
                if (!Current_User::allow('ads', 'approve_ads'))
                {
                    Current_User::disallow();
                    return;
                }

                $version = new Version('ads', $_REQUEST['version_id']);
                $version->loadObject($ad);
                $ad->setApproved(1);
                $ad->save(FALSE);
                $version->setSource($ad);
                $version->setApproved(TRUE);
                if (PHPWS_Error::logIfError($version->save()))
                {
                    Ads_Admin::sendMessage(dgettext('ads', 'An error occurred when saving your version.'), 'approval');
                }
                Ads_Admin::sendMessage(dgettext('ads', 'Ad approved.'), 'approval');
                break;

            case 'versions':
                PHPWS_Core::initModClass('version', 'Restore.php');

                $title = dgettext('ads', 'Old Ad Versions');
                $restore = new Version_Restore('ads', 'ads', $ad->id, 'Ads_Ad', 'approval_view');

                $vars['action'] = 'restoreAd';
                $restore->setRestoreUrl(PHPWS_Text::linkAddress('ads', $vars, TRUE));

                $vars['action'] = 'removeAd';
                $restore->setRemoveUrl(PHPWS_Text::linkAddress('ads', $vars, TRUE));

                $content = $restore->getList();
                break;

            case 'removeAd':
                $version = new Version('ads', $_REQUEST['version_id']);
                $ad_id = $version->source_id;
                if (PHPWS_Error::logIfError($version->delete()))
                {
                    Ads_Admin::sendMessage(dgettext('ads', 'A problem occurred when trying to remove this ad version.'),
                                           array('action'=>'versions', 'ad_id'=>$ad_id));
                }
                Ads_Admin::sendMessage(dgettext('ads', 'Ad version removed.'),
                                       array('action'=>'versions', 'ad_id'=>$ad_id));
                break;

            case 'restoreAd':
                $version = new Version('ads', $_REQUEST['version_id']);
                $ad_id = $version->source_id;
                if (PHPWS_Error::logIfError($version->restore()))
                {
                    Ads_Admin::sendMessage(dgettext('ads', 'A problem occurred when trying to restore this ad version.'),
                                           array('action'=>'versions', 'ad_id'=>$ad_id));
                }
                Ads_Admin::sendMessage(dgettext('ads', 'Ad version restored.'),
                                       array('action'=>'versions', 'ad_id'=>$ad_id));
                break;

            case 'viewAd':
                $title = dgettext('ads', 'View Ad');
                $content = $ad->approval_view();
                break;

            /********************** END AD CASES **********************/
        }

        $template['TITLE'] = &$title;
        if (isset($message))
        {
            $template['MESSAGE'] = &$message;
        }
        $template['CONTENT'] = &$content;

        return PHPWS_Template::process($template, 'ads', 'admin.tpl');
    }

    function sendMessage($message, $command)
    {
        $_SESSION['ads_message'] = $message;
        if (is_array($command))
        {
            PHPWS_Core::reroute(PHPWS_Text::linkAddress('ads', $command, TRUE));
        }

        PHPWS_Core::reroute(PHPWS_Text::linkAddress('ads', array('action'=>$command), TRUE));
    }

    function getMessage()
    {
        if (isset($_SESSION['ads_message']))
        {
            $message = $_SESSION['ads_message'];
            unset($_SESSION['ads_message']);
            return $message;
        }

        return NULL;
    }

    function editZone(&$zone, $js=FALSE, $errors=NULL)
    {
        if (!Current_User::allow('ads', 'edit_zones'))
        {
            Current_User::disallow();
            return;
        }

        $form = new PHPWS_Form;
        $form->addHidden('module', 'ads');

        if ($js)
        {
            $form->addHidden('action', 'postJSZone');
            if (isset($_REQUEST['key_id']))
            {
                $form->addHidden('key_id', (int)$_REQUEST['key_id']);
            }
            $form->addButton('cancel', dgettext('ads', 'Cancel'));
            $form->setExtra('cancel', 'onclick="window.close()"');
        }
        else
        {
            $form->addHidden('action', 'postZone');
        }

        $form->addText('title', $zone->getTitle());
        $form->setLabel('title', dgettext('ads', 'Title'));
        $form->setSize('title', 50, 100);

        if (empty($zone->id))
        {
            $form->addSubmit('submit', dgettext('ads', 'Save New Zone'));
        }
        else
        {
            $form->addHidden('zone_id', $zone->getId());
            $form->addSubmit('submit', dgettext('ads', 'Update Zone'));
        }

        $form->addTextArea('description', $zone->getDescription(FALSE));
        $form->setRows('description', '3');
        $form->setWidth('description', '80%');
        $form->setLabel('description', dgettext('ads', 'Description'));

        $ad_type_list = array(0=> dgettext('ads', 'Banner'), 1=>dgettext('ads', 'Text Ad'));
        $form->addSelect('ad_type', $ad_type_list);
        $form->setMatch('ad_type', $zone->ad_type);
        $form->setLabel('ad_type', dgettext('ads', 'Ad type'));

        $max_num_ads_list = array(1,2,3,4,5,6,7,8,9,10);
        $form->addSelect('max_num_ads', $max_num_ads_list);
        $form->setMatch('max_num_ads', $zone->getMaxNumAds() - 1);
        $form->setLabel('max_num_ads', dgettext('ads', 'Maximum ads displayed'));

        $template = $form->getTemplate();
        if (isset($errors['title']))
        {
            $template['TITLE_ERROR'] = $errors['title'];
        }

        return PHPWS_Template::process($template, 'ads', 'zone/edit.tpl');
    }

    function postZone(&$zone)
    {
        if (empty($_POST['title']))
        {
            $errors['title'] = dgettext('ads', 'Your zone must have a title.');
        }

        $zone->setTitle($_POST['title']);
        if (!empty($_POST['description']))
        {
            $zone->setDescription($_POST['description']);
        }
        $zone->setAdType($_POST['ad_type']);
        $zone->setMaxNumAds($_POST['max_num_ads'] + 1);

        return isset($errors) ? $errors : true;
    }

    function removeZonePin()
    {
        if (isset($_GET['zone_id']))
        {
            $db = new PHPWS_DB('ads_zone_pins');
            $db->addWhere('zone_id', $_GET['zone_id']);
            if (isset($_GET['key_id']))
            {
                $db->addWhere('key_id', $_GET['key_id']);
            }
            PHPWS_Error::logIfError($db->delete());
        }
    }

    function listZones()
    {
        PHPWS_Core::initCoreClass('DBPager.php');

        $pageTags['TITLE']       = dgettext('ads', 'Title');
        $pageTags['DESCRIPTION'] = dgettext('ads', 'Description');
        $pageTags['AD_TYPE']     = dgettext('ads', 'Ad Type');
        $pageTags['MAX_NUM_ADS'] = dgettext('ads', 'Max Ads');
        $pageTags['ACTIVE']      = dgettext('ads', 'Active');
        $pageTags['ACTION']      = dgettext('ads', 'Action');
        $pager = new DBPager('ads_zones', 'Ads_Zone');
        $pager->setModule('ads');
        $pager->setTemplate('zone/list.tpl');
        $pager->addToggle(PHPWS_LIST_TOGGLE_CLASS);
        $pager->addPageTags($pageTags);
        $pager->addRowTags('getTpl');
        $pager->setSearch('title', 'description');
        $pager->setDefaultOrder('title', 'asc');
        $pager->setEmptyMessage(dgettext('ads', 'No zones found.'));
        $pager->cacheQueries();

        return $pager->get();
    }

    function pinZoneAll($zone_id)
    {
        $values['zone_id'] = $zone_id;
        $db = new PHPWS_DB('ads_zone_pins');
        $db->addWhere($values);
        PHPWS_Error::logIfError($db->delete());
        $db->resetWhere();

        $values['key_id'] = -1;
        $db->addValue($values);

        return $db->insert();
    }

    function lockZone($zone_id, $key_id)
    {
        $zone_id = (int)$zone_id;
        $key_id = (int)$key_id;

        unset($_SESSION['Pinned_Ad_Zones'][$zone_id]);

        $values['zone_id'] = $zone_id;
        $values['key_id'] = $key_id;

        $db = new PHPWS_DB('ads_zone_pins');
        $db->addWhere($values);
        PHPWS_Error::logIfError($db->delete());
        $db->addValue($values);
        return $db->insert();
    }

    function selectAdvertiser()
    {
        if (!Current_User::allow('ads', 'edit_advertisers'))
        {
            Current_User::disallow();
            return;
        }

        PHPWS_Core::initModClass('user', 'Users.php');

        $form = new PHPWS_Form;
        $form->addHidden('module', 'ads');
        $form->addHidden('action', 'addAdvertiser');

        $db = new PHPWS_DB('users');
        $db->addColumn('id');
        $db->addColumn('username');
        $db->addColumn('display_name');
        $db->addJoin('left', 'users', 'ads_advertisers', 'id', 'user_id');
        $db->addWhere('ads_advertisers.user_id', null, 'is');
        $result = $db->getObjects('PHPWS_User');

        if ($result)
        {
            foreach ($result as $user)
            {
                $choices[$user->id] = $user->display_name;
            }
            $form->addSelect('advertiser_id', $choices);
            $form->setLabel('advertiser_id', dgettext('ads', 'Available users'));
            $form->addSubmit('submit', dgettext('ads', 'Continue'));
        }
        else
        {
            $form->addTplTag('NO_USERS_NOTE', dgettext('ads', 'Sorry, there are no users available. You will have to create a user account first.'));
        }

        return PHPWS_Template::process($form->getTemplate(), 'ads', 'advertiser/select.tpl');
    }

    function addAdvertiser(&$advertiser)
    {
        if (!Current_User::allow('ads', 'edit_advertisers'))
        {
            Current_User::disallow();
            return;
        }

        $form = new PHPWS_Form;
        $form->addHidden('module', 'ads');
        $form->addHidden('action', 'postAdvertiser');
        $form->addHidden('advertiser_id', $advertiser->user_id);

        $form->addText('business_name', $advertiser->business_name);
        $form->setLabel('business_name', dgettext('ads', 'Business/Organization'));
        $form->setSize('business_name', 50, 255);

        $form->addSubmit('submit', dgettext('ads', 'Save New Advertiser'));

        return PHPWS_Template::process($form->getTemplate(), 'ads', 'advertiser/add.tpl');
    }

    function postAdvertiser(&$advertiser)
    {
        if (!Current_User::allow('ads', 'edit_advertisers'))
        {
            Current_User::disallow();
            return;
        }

        if (!empty($_POST['advertiser_id']) && !empty($_POST['business_name']))
        {
            $advertiser->setBusinessName($_POST['business_name']);
            $advertiser->setCreated(mktime());
            return true;
        }

        return false;
    }

    function listAdvertisers()
    {
        PHPWS_Core::initCoreClass('DBPager.php');

        $pageTags['DISPLAY_NAME'] = dgettext('ads', 'Username');
        $pageTags['BUSINESS_NAME'] = dgettext('ads', 'Business Name');
        $pageTags['CREATED']  = dgettext('ads', 'Advertising Since');
        $pageTags['ACTION']   = dgettext('ads', 'Action');
        $pager = new DBPager('ads_advertisers', 'Ads_Advertiser');
        $pager->setModule('ads');
        $pager->setTemplate('advertiser/list.tpl');
        $pager->addToggle(PHPWS_LIST_TOGGLE_CLASS);
        $pager->addPageTags($pageTags);
        $pager->addRowTags('getTpl');
        $pager->joinResult('user_id', 'demographics', 'user_id', 'business_name', 'business_name');
        $pager->joinResult('user_id', 'users', 'id', 'display_name', 'display_name');
        $pager->setSearch('demographics.business_name');
        $pager->setDefaultOrder('demographics.business_name', 'asc');
        $pager->setEmptyMessage(dgettext('ads', 'No advertisers found.'));
        $pager->cacheQueries();

        return $pager->get();
    }

    function editCampaign(&$campaign, $errors=NULL)
    {
        $form = new PHPWS_Form;
        $form->addHidden('module', 'ads');
        $form->addHidden('action', 'postCampaign');
        $form->addHidden('advertiser_id', $campaign->getAdvertiserId());

        $form->addText('name', $campaign->getName());
        $form->setLabel('name', dgettext('ads', 'Title'));
        $form->setSize('name', 50, 100);

        $priority_list = array(1=> dgettext('ads', '1 (Lowest)'), 2=>2, 3=>3, 4=>4, 5=>5, 6=>6,
                               7=>7, 8=>8, 9=>9, 10=> dgettext('ads', '10 (Highest)'));
        $form->addSelect('priority', $priority_list);
        $form->setMatch('priority', $campaign->priority);
        $form->setLabel('priority', dgettext('ads', 'Priority'));

        if (empty($campaign->id))
        {
            $form->addSubmit('submit', dgettext('ads', 'Save New Campaign'));
        }
        else
        {
            $form->addHidden('campaign_id', $campaign->getId());
            $form->addSubmit('submit', dgettext('ads', 'Update Campaign'));
        }

        $template = $form->getTemplate();
        if (isset($errors['name']))
        {
            $template['NAME_ERROR'] = $errors['name'];
        }

        return PHPWS_Template::process($template, 'ads', 'campaign/edit.tpl');
    }

    function postCampaign(&$campaign)
    {
        if (empty($_POST['name']))
        {
            $errors['name'] = dgettext('ads', 'Your campaign must have a title.');
        }

        $campaign->setName($_POST['name']);
        $campaign->setAdvertiserId($_POST['advertiser_id']);
        $campaign->setPriority($_POST['priority']);
        $campaign->setCreated(mktime());

        return isset($errors) ? $errors : true;
    }

    function removeCampaignPin()
    {
        if (isset($_GET['campaign_id']) && isset($_GET['zone_id']))
        {
            $db = new PHPWS_DB('ads_campaign_pins');
            $db->addWhere('zone_id', $_GET['zone_id']);
            $db->addWhere('campaign_id', $_GET['campaign_id']);
            PHPWS_Error::logIfError($db->delete());
        }
    }

    function listCampaigns($advertiser_id)
    {
        PHPWS_Core::initCoreClass('DBPager.php');

        $vars['action'] = 'newCampaign';
        $vars['advertiser_id'] = $advertiser_id;
        $pageTags['ADD_LINK'] = PHPWS_Text::secureLink(dgettext('ads', 'Add new campaign'), 'ads', $vars);
        $pageTags['NAME']     = dgettext('ads', 'Name');
        $pageTags['ADS']      = dgettext('ads', 'Ads');
        $pageTags['CREATED']  = dgettext('ads', 'Created');
        $pageTags['ACTION']   = dgettext('ads', 'Action');
        $pager = new DBPager('ads_campaigns', 'Ads_Campaign');
        $pager->setModule('ads');
        $pager->setTemplate('campaign/list.tpl');
        $pager->addToggle(PHPWS_LIST_TOGGLE_CLASS);
        $pager->addPageTags($pageTags);
        $pager->addRowTags('getTpl');
        $pager->setSearch('name');
        $pager->setDefaultOrder('name', 'asc');
        $pager->addWhere('advertiser_id', $advertiser_id);
        $pager->setEmptyMessage(dgettext('ads', 'No campaigns found.'));
        $pager->cacheQueries();

        return $pager->get();
    }

    function lockCampaign($campaign_id, $zone_id)
    {
        $zone_id = (int)$zone_id;
        $campaign_id = (int)$campaign_id;

        unset($_SESSION['Pinned_Ad_Campaigns'][$campaign_id]);

        $values['zone_id'] = $zone_id;
        $values['campaign_id'] = $campaign_id;

        $db = new PHPWS_DB('ads_campaign_pins');
        $db->addWhere($values);
        PHPWS_Error::logIfError($db->delete());
        $db->addValue($values);
        return $db->insert();
    }

    function editAd(&$ad, $errors=NULL, $approvalList=FALSE)
    {
        PHPWS_Core::initModClass('version', 'Version.php');
        $version = new Version('ads');
        $version->setSource($ad);
        $approval_id = $version->isWaitingApproval();
        if ($approval_id)
        {
            $version->setId($approval_id);
            $version->init();
            $version->loadObject($ad);
        }

        $form = new PHPWS_Form;
        $form->addHidden('module', 'ads');
        $form->addHidden('action', 'postAd');
        $form->addHidden('campaign_id', $ad->getCampaignId());
        $form->addHidden('ad_type', $ad->ad_type);
        if (isset($approval_id))
        {
            $form->addHidden('ad_version_id', $approval_id);
        }
        if ($approvalList)
        {
            $form->addHidden('approvalList', 1);
        }

        $form->addText('title', $ad->getTitle());
        $form->setLabel('title', dgettext('ads', 'Title'));
        $form->setSize('title', 50, 100);

        if ($ad->ad_type == 1)
        {
            $form->addTextArea('ad_text', $ad->getAdText(FALSE));
            $form->setRows('ad_text', '4');
            $form->setWidth('ad_text', '80%');
            $form->setLabel('ad_text', dgettext('ads', 'Ad Text'));
        }

        $form->addText('url', $ad->getUrl());
        $form->setLabel('url', dgettext('ads', 'URL'));
        $form->setSize('url', 44, 255);

        if (empty($ad->id))
        {
            if ($ad->ad_type == 0)
            {
                $form->addFile('filename');
                $form->setLabel('filename', dgettext('ads', 'Banner to Upload'));
                $form->setSize('filename', 35);
            }

            $form->addSubmit('submit', dgettext('ads', 'Save New Ad'));
        }
        else
        {
            $form->addHidden('ad_id', $ad->getId());
            $form->addSubmit('submit', dgettext('ads', 'Update Ad'));
        }

        $template = $form->getTemplate();
        if (isset($errors['title']))
        {
            $template['TITLE_ERROR'] = $errors['title'];
        }
        if (isset($errors['filename']))
        {
            $template['FILENAME_ERROR'] = $errors['filename'];
        }
        if (isset($errors['ad_text']))
        {
            $template['AD_TEXT_ERROR'] = $errors['ad_text'];
        }

        return PHPWS_Template::process($template, 'ads', 'ad/edit.tpl');
    }

    function postAd(&$ad)
    {
        PHPWS_Core::initModClass('filecabinet', 'Image.php');

        if (empty($_POST['title']))
        {
            $errors['title'] = dgettext('ads', 'Your ad must have a title.');
        }
        if ($ad->ad_type == 1)
        {
            if (empty($_POST['ad_text']))
            {
                $errors['ad_text'] = dgettext('ads', 'Your ad must have some ad text.');
            }

            $ad->setAdText($_POST['ad_text']);
        }

        $ad->setTitle($_POST['title']);
        $ad->setUrl($_POST['url']);

        if (isset($_POST['ad_version_id']) || Current_User::isRestricted('ads'))
        {
            $ad->setApproved(0);
        }
        else
        {
            $ad->setApproved(1);
        }

        if (empty($ad->id) && !isset($errors))
        {
            $ad->setCreated(mktime());

            if ($ad->ad_type == 0)
            {
                $image = new PHPWS_Image;
                $image->setDirectory('images/ads/');
                if (!$image->importPost('filename'))
                {
                    if (isset($image->_errors) && sizeof($image->_errors))
                    {
                        foreach ($image->_errors as $oError)
                        {
                            $imageErrors[] = $oError->getMessage();
                        }
                        $errors['filename'] = implode(' ', $imageErrors);
                    }
                    else
                    {
                        $errors['filename'] = dgettext('ads', 'Please specify a valid file to upload.');
                    }
                }
                else
                {
                    $image->setFilename($ad->created . '_' . $image->file_name);
                    $result = $image->write();
                    if (PHPWS_Error::logIfError($result))
                    {
                        $errors['filename'] = dgettext('ads', 'There was a problem saving your image.');
                    }
                    else
                    {
                        $ad->setFilename($image->file_name);
                        $ad->setWidth($image->width);
                        $ad->setHeight($image->height);
                    }
                }
            }
        }

        return isset($errors) ? $errors : true;
    }

    function listAds($campaign_id)
    {
        PHPWS_Core::initCoreClass('DBPager.php');

        $vars['action'] = 'newAd';
        $vars['campaign_id'] = $campaign_id;
        $vars['ad_type'] = 0;
        $pageTags['BANNER_LINK'] = PHPWS_Text::secureLink(dgettext('ads', 'banner ad'), 'ads', $vars);
        $vars['ad_type'] = 1;
        $pageTags['TEXT_AD_LINK'] = PHPWS_Text::secureLink(dgettext('ads', 'text ad'), 'ads', $vars);
        $pageTags['ADD_NEW']  = dgettext('ads', 'Add new');
        $pageTags['NAME']     = dgettext('ads', 'Name');
        $pageTags['TYPE']     = dgettext('ads', 'Type');
        $pageTags['ACTIVE']   = dgettext('ads', 'Active');
        $pageTags['APPROVED'] = dgettext('ads', 'Approved');
        $pageTags['ACTION']   = dgettext('ads', 'Action');
        $pager = new DBPager('ads', 'Ads_Ad');
        $pager->setModule('ads');
        $pager->setTemplate('ad/list.tpl');
        $pager->addToggle(PHPWS_LIST_TOGGLE_CLASS);
        $pager->addPageTags($pageTags);
        $pager->addRowTags('getTpl');
        $pager->setSearch('title');
        $pager->setDefaultOrder('title', 'asc');
        $pager->addWhere('campaign_id', $campaign_id);
        $pager->setEmptyMessage(dgettext('ads', 'No ads found.'));
        $pager->cacheQueries();

        return $pager->get();
    }
}
?>