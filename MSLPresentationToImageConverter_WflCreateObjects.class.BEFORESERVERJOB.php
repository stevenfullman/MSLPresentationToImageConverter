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
require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
require_once BASEDIR . '/server/bizclasses/BizRelation.class.php';
require_once BASEDIR . '/server/services/wfl/WflCreateObjectsService.class.php';
require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';

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
		
		foreach( $req->Objects as $object ) { 
		
			// We're only interested in presentations
			
			if ( strtolower( $object->MetaData->BasicMetaData->Type ) == 'presentation' ) {
				
				// Check if the new image is being uploaded to a dossier
				
				if ( !empty( $object->Relations ) ) {
				
					if ( $object->Relations[0]->Type == 'Contained' ) {
					
							// Get the dossier ID so we can create relation with cloned image
				
							$dossierId = $object->Relations[0]->Parent;
						
					}
				}

				try {
				
					// Create images if 'Only Exec If Created In Dossier' flag is true AND the uploaded presentation is in a dossier
					// Or create images anyway if the flag is false -- and we'll create the relation after if the image is in a dossier
						
					if ( ( MSLP2IC_ONLY_EXEC_IF_CREATED_IN_DOSSIER && $dossierId != 0 ) || !MSLP2IC_ONLY_EXEC_IF_CREATED_IN_DOSSIER ) {
					
						$presentationId           = $object->MetaData->BasicMetaData->ID;
						$presentationName         = $object->MetaData->BasicMetaData->Name;
						$presentationBrandId      = $object->MetaData->BasicMetaData->Publication->Id;
						$presentationBrandName    = $object->MetaData->BasicMetaData->Publication->Name;
						$presentationCategoryId   = $object->MetaData->BasicMetaData->Category->Id;
						$presentationCategoryName = $object->MetaData->BasicMetaData->Category->Name;
						$presentationIssueId      = $object->MetaData->BasicMetaData->Category->Name;
						$presentationObject       = BizObject::getObject( $presentationId, $user, false, 'native');
						$presentationMeta         = BizObject::getObject( $presentationId, $user, false, 'native');
						$presentationExt          = MimeTypeHandler::mimeType2FileExt( $presentationObject->MetaData->ContentMetaData->Format, $presentationObject->MetaData->BasicMetaData->Type );
						$presentationData         = file_get_contents( $presentationObject->Files[0]->FilePath );

						// Create the individual PNG files from each slide

						$presentationImages = self::createImagesFromPresentation( $presentationId, $presentationExt, $presentationData );

						// Create new image objects for each of the PNGs

						$tmpDir = TEMPDIRECTORY . '/' . $presentationId . '/';

						$i = 0;

						foreach ($presentationImages as $presentationImage ) {

							$i++;

							// Get the image data

							$imageData = file_get_contents( $tmpDir . $presentationImage );
							$imageMeta = clone $presentationMeta;

							$attachment     = new Attachment( 'native', 'image/png' );
							$transferServer = new BizTransferServer();
							$transferServer->writeContentToFileTransferServer( $imageData, $attachment );
				
							$files   = array();
							$files[] = $attachment;
							
							$imageObject                                             = new Object();
					        $imageObject->MetaData                                   = new MetaData();
					        $imageObject->MetaData->BasicMetaData                    = new BasicMetaData();
					        $imageObject->MetaData->BasicMetaData->Name              = $presentationName . '_SLIDE_' . $i . '_OF_' . count( $presentationImages );
					        $imageObject->MetaData->BasicMetaData->Type              = 'Image';
					        $imageObject->MetaData->ContentMetaData                  = new ContentMetaData();
					        $imageObject->MetaData->ContentMetaData->Format          = self::isKnownMimeType($file, '');
					        $imageObject->MetaData->ContentMetaData->FileSize        = FileSize($file);
					        $imageObject->MetaData->BasicMetaData->Publication       = new Publication();
					        $imageObject->MetaData->BasicMetaData->Publication->Id   = $presentationBrandId;
					        $imageObject->MetaData->BasicMetaData->Publication->Name = $presentationBrandName;
					        $imageObject->MetaData->BasicMetaData->Category          = new Category();
					        $imageObject->MetaData->BasicMetaData->Category->Id      = $presentationCategoryId;
					        $imageObject->MetaData->BasicMetaData->Category->Name    = $presentationCategoryName;
					        $imageObject->MetaData->WorkflowMetaData                 = new WorkflowMetaData();
					        $imageObject->MetaData->WorkflowMetaData->State          = new State();
					        $imageObject->MetaData->WorkflowMetaData->State->Id      = 96;
					        $imageObject->MetaData->WorkflowMetaData->State->Name    = 'Image Draft';
					        $imageObject->Files                                      = array( $attachment );

					        // Create the new object

							$service      = new WflCreateObjectsService();
							$req          = new WflCreateObjectsRequest();
							$req->Ticket  = $ticket;
							$req->Lock    = false;
							$req->Objects = array( $imageObject );
							$resp         = $service->execute( $req );
							
							foreach ( $resp->Objects as $newObject ) {
								$imageId = $newObject->MetaData->BasicMetaData->ID;
							}

							if ( $dossierId != 0 ) {
						
								// Add the new web image to the dossier if the original presentation is contained
					
								$newRelation = array( new Relation( $dossierId, $imageId, 'Contained' ) );
					
								BizRelation::createObjectRelations( $newRelation, $user, null, true );	
							}
						}
					}
				}
				catch (Exception $e) {
					throw new BizException( null, 'Server', __METHOD__, 
					'Problem creating images from presentation slides.' );						
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

	public static function createImagesFromPresentation( $presentationId, $presentationExt, $presentationData ) {

		// 1. Create the temporary file for the presentation

		$tmpDir            = TEMPDIRECTORY . '/' . $presentationId;
		$baseFileName      = $tmpDir . '/'   . $presentationId;
		$inputFileName     = $baseFileName   . $presentationExt;

		// 2. Create the temporary input file and add the presentation data to it

		if( !is_dir( $tmpDir ) ) {
			if ( !mkdir( $tmpDir, 0777 ) ) {
				LogHandler::Log('MSLPresentationToImageConverter', 'ERROR', "Conversion failed. Can't create tmp directory: $tmpDir." );
				return null;
			}
		}
		$tmpIn = fopen( $inputFileName, 'w' );
		if ( !$tmpIn ) {
			LogHandler::Log('MSLPresentationToImageConverter', 'ERROR', "Conversion failed. Can't write to input file $inputFilename." );
			unlink( $inputFileName );
			return null;
		}
		fwrite( $tmpIn, $presentationData );
		fclose( $tmpIn );

		// 3. Convert the presentation to PDF

		$convertToPdfCommand = '/usr/local/bin/soffice --headless --convert-to pdf ' . $inputFileName . ' --outdir ' . $tmpDir . ' 2>&1';

		$result = shell_exec( $convertToPdfCommand );

		// 4. Convert the PDF into individual PNG files

		$pdfFileName = $baseFileName . '.pdf';

		$convertToPngCommand = '/usr/local/bin/gs -dNOPAUSE -sDEVICE=png16m -r256 -sOutputFile=' . $baseFileName . '_%03d.png ' . $pdfFileName . ' 2>&1';

		$result = shell_exec( $convertToPngCommand );

		// 5. Return an array of all the individual PNG file names and tmpdir path

		$tmpDirFiles        = array_diff( scandir( $tmpDir ), array( '..', '.' ) );
		$presentationImages = array();
		
		foreach ( $tmpDirFiles as $tmpDirFile ) {
			$pathParts = pathinfo( $tmpDirFile );
			if ( strtolower( $pathParts['extension'] ) == 'png' ) {
				$presentationImages[] = $tmpDirFile;
			}
		}

		return $presentationImages;
	}

	/**
	 * Check if the file is of a know mimetype to Enterprise
	 *
	 * @param  string    filename
	 * @param  string    ''
	 * @return string    the format of the file 
	 */
	function isKnownMimeType($filename, $filetype)
	{
	    $filetype = MimeTypeHandler::filename2ObjType($format, $filename);
	    if ($format == '') {
	        return false;
	    }
	    else {
	        return $format;
	    }
	}

	/*
	 * Create the metadata for the file that is being uploaded against the article
	 *
	 * @param  string    full file path of file to upload
	 * @return object    contains all information for the file upload 
	 */
	function createFilesMetaData($file)
	{
	    $filetype       = '';
	    $format         = self::isKnownMimeType($file, $filetype);
	    $transferServer = new BizTransferServer();
	    $files          = array();
	    
	    $attachment            = new Attachment();
	    $attachment->Rendition = 'native';
	    $attachment->Type      = $format; // mime file type, for this demo assumed to always be jpg
	    $transferServer->copyToFileTransferServer($file, $attachment);
	    $files[] = $attachment;
	    
	    return $files;
	}

	/**
	 * Build the issue and channel information for the Dossier or Article being created / updated
	 *
	 * @param  string    username of woodwing user
	 * @param  string    brand id of the required article / dossier to create
	 * @param  string    issue id of the of equired article / dossier to create
	 * @param  string    brand channel of the required article / dossier to  create [print/web]
	 * @param  array     users email address and job ticket id
	 * @return object    the new target object created
	 */
	function buildObjectTarget($user_name, $brand_id, $issue_id, $brand_channel = 'print', $email_info = null)
	{
	    try {
	        require_once ENTERPRISE_BASEDIR . '/server/bizclasses/BizPublication.class.php';
	        require_once ENTERPRISE_BASEDIR . '/server/dbclasses/DBAdmIssue.class.php';
	        
	        $brand_object = BizPublication::getPublications($user_name, 'browse', $brand_id);
	        
	        foreach ($brand_object[0]->PubChannels as $pub_channel) {
	            if (strtolower($pub_channel->Type) == strtolower($brand_channel)) {
	                $pub_channel_object = $pub_channel;
	                break;
	            }
	        }
	        
	        foreach ($pub_channel_object->Issues as $pub_channel_issue) {
	            if ($pub_channel_issue->Id == $issue_id) {
	                $issue_id   = $pub_channel_issue->Id;
	                $issue_name = $pub_channel_issue->Name;
	            }
	        }
	        
	        // Set Issue Info
	        $issue_object       = new Issue();
	        $issue_object->Id   = $issue_id;
	        $issue_object->Name = $issue_name;
	        
	        // Set PubChannel Info
	        $channel_object       = new Pubchannel();
	        $channel_object->Id   = $pub_channel_object->Id;
	        $channel_object->Name = $pub_channel_object->Name;
	        
	        // Set Empty target object to be populated and returned.
	        $target_object                = array();
	        $target_object[0]             = new Target();
	        $target_object[0]->PubChannel = $channel_object;
	        
	        $target_object[0]->Issue = $issue_object;
	        
	        return $target_object;
	    }
	    catch (BizException $e) {
	        sendEmail($email_info, '', $e->getMessage(), true);
	        exit;
	    }
	}
}
