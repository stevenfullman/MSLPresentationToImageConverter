<?php

/****************************************************************************
   Copyright 2020 WoodWing Software BV

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
****************************************************************************/

require_once BASEDIR.'/server/interfaces/plugins/EnterprisePlugin.class.php';

class MSLPresentationToImageConverter_EnterprisePlugin extends EnterprisePlugin
{
	public function getPluginInfo()
	{ 
		require_once BASEDIR.'/server/interfaces/plugins/PluginInfoData.class.php';
		$info = new PluginInfoData(); 
		$info->DisplayName = 'MSL Presentation To ImageConverter';
		$info->Version     = '1.0'; // don't use PRODUCTVERSION
		$info->Description = 'Converts presentation slides to individual images when uploaded to dossier';
		$info->Copyright   = 'Media Systems Ltd ' . date('Y');
		return $info;
	}
	
	final public function getConnectorInterfaces() 
	{ 
		return array(

// ads services
			// 'AdsGetSettings_EnterpriseConnector',
			// 'AdsGetQueries_EnterpriseConnector',
			// 'AdsGetDatasource_EnterpriseConnector',
			// 'AdsGetQueryFields_EnterpriseConnector',
			// 'AdsGetDatasourceInfo_EnterpriseConnector',
			// 'AdsNewDatasource_EnterpriseConnector',
			// 'AdsSavePublication_EnterpriseConnector',
			// 'AdsDeletePublication_EnterpriseConnector',
			// 'AdsQueryDatasources_EnterpriseConnector',
			// 'AdsCopyDatasource_EnterpriseConnector',
			// 'AdsSaveSetting_EnterpriseConnector',
			// 'AdsSaveDatasource_EnterpriseConnector',
			// 'AdsDeleteQueryField_EnterpriseConnector',
			// 'AdsNewQuery_EnterpriseConnector',
			// 'AdsGetSettingsDetails_EnterpriseConnector',
			// 'AdsDeleteQuery_EnterpriseConnector',
			// 'AdsGetDatasourceType_EnterpriseConnector',
			// 'AdsSaveQuery_EnterpriseConnector',
			// 'AdsGetQuery_EnterpriseConnector',
			// 'AdsGetDatasourceTypes_EnterpriseConnector',
			// 'AdsCopyQuery_EnterpriseConnector',
			// 'AdsGetPublications_EnterpriseConnector',
			// 'AdsDeleteDatasource_EnterpriseConnector',
			// 'AdsSaveQueryField_EnterpriseConnector',

// dat services
			// 'DatSetRecords_EnterpriseConnector',
			// 'DatGetRecords_EnterpriseConnector',
			// 'DatHasUpdates_EnterpriseConnector',
			// 'DatOnSave_EnterpriseConnector',
			// 'DatGetUpdates_EnterpriseConnector',
			// 'DatQueryDatasources_EnterpriseConnector',
			// 'DatGetDatasource_EnterpriseConnector',

// wfl services
			// 'WflPreviewArticleAtWorkspace_EnterpriseConnector',
			// 'WflGetPages_EnterpriseConnector',
			// 'WflCopyObject_EnterpriseConnector',
			// 'WflDeleteUserSettings_EnterpriseConnector',
			// 'WflGetStates_EnterpriseConnector',
			// 'WflQueryObjects_EnterpriseConnector',
			// 'WflDeleteObjectRelations_EnterpriseConnector',
			// 'WflChangeOnlineStatus_EnterpriseConnector',
			// 'WflCheckSpelling_EnterpriseConnector',
			// 'WflGetSuggestions_EnterpriseConnector',
			// 'WflInstantiateTemplate_EnterpriseConnector',
			// 'WflSaveObjects_EnterpriseConnector',
			// 'WflUnlockObjects_EnterpriseConnector',
			// 'WflListArticleWorkspaces_EnterpriseConnector',
			// 'WflListVersions_EnterpriseConnector',
			// 'WflRestoreObjects_EnterpriseConnector',
			// 'WflSendMessages_EnterpriseConnector',
			// 'WflGetPagesInfo_EnterpriseConnector',
			// 'WflNamedQuery_EnterpriseConnector',
			// 'WflGetRelatedPagesInfo_EnterpriseConnector',
			// 'WflRestoreVersion_EnterpriseConnector',
			// 'WflUpdateObjectTargets_EnterpriseConnector',
			// 'WflPreviewArticlesAtWorkspace_EnterpriseConnector',
			// 'WflCheckSpellingAndSuggest_EnterpriseConnector',
			// 'WflChangePassword_EnterpriseConnector',
			// 'WflLogOn_EnterpriseConnector',
			// 'WflAddObjectLabels_EnterpriseConnector',
			// 'WflGetUserSettings_EnterpriseConnector',
			// 'WflMultiSetObjectProperties_EnterpriseConnector',
			// 'WflGetObjects_EnterpriseConnector',
			// 'WflGetArticleFromWorkspace_EnterpriseConnector',
			// 'WflDeleteObjectTargets_EnterpriseConnector',
			// 'WflGetDialog2_EnterpriseConnector',
			// 'WflGetObjectRelations_EnterpriseConnector',
			// 'WflCreateObjectTargets_EnterpriseConnector',
			// 'WflCreateObjectLabels_EnterpriseConnector',
			// 'WflGetVersion_EnterpriseConnector',
			// 'WflSendToNext_EnterpriseConnector',
			// 'WflLockObjects_EnterpriseConnector',
			// 'WflGetServers_EnterpriseConnector',
			// 'WflDeleteObjects_EnterpriseConnector',
			// 'WflCreateObjectOperations_EnterpriseConnector',
			// 'WflCreateObjectRelations_EnterpriseConnector',
			// 'WflSaveArticleInWorkspace_EnterpriseConnector',
			// 'WflSendTo_EnterpriseConnector',
			// 'WflLogOff_EnterpriseConnector',
			// 'WflSetObjectProperties_EnterpriseConnector',
			 'WflCreateObjects_EnterpriseConnector',
			// 'WflAutocomplete_EnterpriseConnector',
			// 'WflSaveUserSettings_EnterpriseConnector',
			// 'WflSuggestions_EnterpriseConnector',
			// 'WflDeleteArticleWorkspace_EnterpriseConnector',
			// 'WflCreateArticleWorkspace_EnterpriseConnector',
			// 'WflGetRelatedPages_EnterpriseConnector',
			// 'WflUpdateObjectRelations_EnterpriseConnector',
			// 'WflUpdateObjectLabels_EnterpriseConnector',
			// 'WflDeleteObjectLabels_EnterpriseConnector',
			// 'WflRemoveObjectLabels_EnterpriseConnector',

// sys services
			// 'SysGetSubApplications_EnterpriseConnector',

// pln services
			// 'PlnLogOff_EnterpriseConnector',
			// 'PlnLogOn_EnterpriseConnector',
			// 'PlnModifyAdverts_EnterpriseConnector',
			// 'PlnModifyLayouts_EnterpriseConnector',
			// 'PlnDeleteAdverts_EnterpriseConnector',
			// 'PlnCreateAdverts_EnterpriseConnector',
			// 'PlnDeleteLayouts_EnterpriseConnector',
			// 'PlnCreateLayouts_EnterpriseConnector',

// adm services
			// 'AdmDeleteUsers_EnterpriseConnector',
			// 'AdmCreateEditions_EnterpriseConnector',
			// 'AdmModifyWorkflowUserGroupAuthorizations_EnterpriseConnector',
			// 'AdmCreateWorkflowUserGroupAuthorizations_EnterpriseConnector',
			// 'AdmCreateUsers_EnterpriseConnector',
			// 'AdmModifyEditions_EnterpriseConnector',
			// 'AdmGetRoutings_EnterpriseConnector',
			// 'AdmGetAutocompleteTerms_EnterpriseConnector',
			// 'AdmDeleteIssues_EnterpriseConnector',
			// 'AdmCreateAutocompleteTermEntities_EnterpriseConnector',
			// 'AdmModifyAccessProfiles_EnterpriseConnector',
			// 'AdmGetPublications_EnterpriseConnector',
			// 'AdmLogOff_EnterpriseConnector',
			// 'AdmDeleteRoutings_EnterpriseConnector',
			// 'AdmDeleteAccessProfiles_EnterpriseConnector',
			// 'AdmModifyPublications_EnterpriseConnector',
			// 'AdmDeletePublicationAdminAuthorizations_EnterpriseConnector',
			// 'AdmAddTemplateObjects_EnterpriseConnector',
			// 'AdmDeleteAutocompleteTerms_EnterpriseConnector',
			// 'AdmCreateAccessProfiles_EnterpriseConnector',
			// 'AdmCreateUserGroups_EnterpriseConnector',
			// 'AdmGetUsers_EnterpriseConnector',
			// 'AdmDeleteAutocompleteTermEntities_EnterpriseConnector',
			// 'AdmGetSections_EnterpriseConnector',
			// 'AdmLogOn_EnterpriseConnector',
			// 'AdmCreateIssues_EnterpriseConnector',
			// 'AdmGetStatuses_EnterpriseConnector',
			// 'AdmAddGroupsToUser_EnterpriseConnector',
			// 'AdmDeleteStatuses_EnterpriseConnector',
			// 'AdmGetIssues_EnterpriseConnector',
			// 'AdmDeleteSections_EnterpriseConnector',
			// 'AdmCreateRoutings_EnterpriseConnector',
			// 'AdmRemoveUsersFromGroup_EnterpriseConnector',
			// 'AdmRemoveTemplateObjects_EnterpriseConnector',
			// 'AdmModifyUsers_EnterpriseConnector',
			// 'AdmCreateAutocompleteTerms_EnterpriseConnector',
			// 'AdmGetTemplateObjects_EnterpriseConnector',
			// 'AdmCreatePublications_EnterpriseConnector',
			// 'AdmRemoveGroupsFromUser_EnterpriseConnector',
			// 'AdmGetUserGroups_EnterpriseConnector',
			// 'AdmModifyAutocompleteTerms_EnterpriseConnector',
			// 'AdmGetAccessProfiles_EnterpriseConnector',
			// 'AdmGetAutocompleteTermEntities_EnterpriseConnector',
			// 'AdmCreatePubChannels_EnterpriseConnector',
			// 'AdmCreatePublicationAdminAuthorizations_EnterpriseConnector',
			// 'AdmGetEditions_EnterpriseConnector',
			// 'AdmGetPublicationAdminAuthorizations_EnterpriseConnector',
			// 'AdmGetPubChannels_EnterpriseConnector',
			// 'AdmModifyPubChannels_EnterpriseConnector',
			// 'AdmModifyRoutings_EnterpriseConnector',
			// 'AdmDeleteEditions_EnterpriseConnector',
			// 'AdmModifyIssues_EnterpriseConnector',
			// 'AdmDeleteWorkflowUserGroupAuthorizations_EnterpriseConnector',
			// 'AdmCreateSections_EnterpriseConnector',
			// 'AdmDeletePublications_EnterpriseConnector',
			// 'AdmModifyUserGroups_EnterpriseConnector',
			// 'AdmCreateStatuses_EnterpriseConnector',
			// 'AdmDeletePubChannels_EnterpriseConnector',
			// 'AdmModifyAutocompleteTermEntities_EnterpriseConnector',
			// 'AdmGetWorkflowUserGroupAuthorizations_EnterpriseConnector',
			// 'AdmModifyStatuses_EnterpriseConnector',
			// 'AdmAddUsersToGroup_EnterpriseConnector',
			// 'AdmModifySections_EnterpriseConnector',
			// 'AdmDeleteUserGroups_EnterpriseConnector',
			// 'AdmCopyIssues_EnterpriseConnector',

// pub services
			// 'PubUnPublishDossiers_EnterpriseConnector',
			// 'PubPublishDossiers_EnterpriseConnector',
			// 'PubUpdateDossierOrder_EnterpriseConnector',
			// 'PubOperationProgress_EnterpriseConnector',
			// 'PubPreviewDossiers_EnterpriseConnector',
			// 'PubGetDossierURL_EnterpriseConnector',
			// 'PubSetPublishInfo_EnterpriseConnector',
			// 'PubUpdateDossiers_EnterpriseConnector',
			// 'PubGetPublishInfo_EnterpriseConnector',
			// 'PubAbortOperation_EnterpriseConnector',
			// 'PubGetDossierOrder_EnterpriseConnector',

// business connectors
			// 'ModifyVariantMetaData_EnterpriseConnector',
			// 'Search_EnterpriseConnector',
			// 'DbModel_EnterpriseConnector',
			// 'CustomObjectMetaData_EnterpriseConnector',
			// 'ServerJob_EnterpriseConnector',
			// 'Preview_EnterpriseConnector',
			// 'IssueEvent_EnterpriseConnector',
			// 'Spelling_EnterpriseConnector',
			// 'FileStore_EnterpriseConnector',
			// 'ConfigFiles_EnterpriseConnector',
			// 'PubPublishing_EnterpriseConnector',
			// 'ContentSource_EnterpriseConnector',
			// 'AutocompleteProvider_EnterpriseConnector',
			// 'MetaData_EnterpriseConnector',
			// 'SuggestionProvider_EnterpriseConnector',
			// 'ObjectEvent_EnterpriseConnector',
			// 'Session_EnterpriseConnector',
			// 'DataSource_EnterpriseConnector',
			// 'AdminProperties_EnterpriseConnector',
			// 'ImageConverter_EnterpriseConnector',
			// 'Version_EnterpriseConnector',
			// 'WebApps_EnterpriseConnector',
			// 'AutomatedPrintWorkflow_EnterpriseConnector',
			// 'FeatureAccess_EnterpriseConnector',
			// 'InDesignServerJob_EnterpriseConnector',
			// 'NameValidation_EnterpriseConnector',

		);
	}
}