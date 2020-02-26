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

require_once BASEDIR . '/server/interfaces/services/wfl/WflCreateObjects_EnterpriseConnector.class.php';

class MSLPresentationToImageConverter_WflCreateObjects extends WflCreateObjects_EnterpriseConnector
{
	final public function getPrio()     { return self::PRIO_DEFAULT; }
	final public function getRunMode()  { return self::RUNMODE_AFTER; }

	final public function runBefore( WflCreateObjectsRequest &$req )
	{} 

	final public function runAfter( WflCreateObjectsRequest $req, WflCreateObjectsResponse &$resp )
	{
		LogHandler::Log( 'MSLPresentationToImageConverter', 'DEBUG', 'Called: MSLPresentationToImageConverter_WflCreateObjects->runAfter()' );
		
		require_once dirname(__FILE__) . '/config.php';

		$ticket = $req->Ticket;
		$user   = BizSession::getUserInfo('user');

		file_put_contents('$req.txt', print_r( $req, true));

		// Assume the image has not been uploaded to a dossier
		
		$dossierId = 0;
		
		// Iterate through objects
		
		foreach( $req->Objects as $obj ) { 
		
			// We're only interested in presentations
			
			if ( strtolower( $obj->MetaData->BasicMetaData->Type ) == 'presentation' ) {
				
				// Check if the new image is being uploaded to a dossier
				
				if ( !empty( $obj->Relations ) ) {
				
					if ( $obj->Relations[0]->Type == 'Contained' ) {
					
							// Get the dossier ID so we can create relation with cloned image
				
							$dossierId = $obj->Relations[0]->Parent;

							file_put_contents('$dossierId.txt', $dossierId);
						
					}
				}
			}
		}

		LogHandler::Log( 'MSLPresentationToImageConverter', 'DEBUG', 'Returns: MSLPresentationToImageConverter_WflCreateObjects->runAfter()' );
	} 
	
	final public function onError( WflCreateObjectsRequest $req, BizException $e )
	{} 
	
	// Not called.
	final public function runOverruled( WflCreateObjectsRequest $req )
	{}
}
