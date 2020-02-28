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

require_once dirname(__FILE__) . '/config.php';
require_once BASEDIR . '/server/interfaces/plugins/connectors/ServerJob_EnterpriseConnector.class.php';
require_once BASEDIR . '/server/bizclasses/BizTransferServer.class.php';
require_once BASEDIR . '/server/bizclasses/BizRelation.class.php';
require_once BASEDIR . '/server/bizclasses/BizSession.class.php';
require_once BASEDIR . '/server/bizclasses/BizObject.class.php';
require_once BASEDIR . '/server/bizclasses/BizWorkflow.class.php';
require_once BASEDIR . '/server/services/wfl/WflCreateObjectsService.class.php';
require_once BASEDIR . '/server/utils/MimeTypeHandler.class.php';

class MSLPresentationToImageConverter_ServerJob extends ServerJob_EnterpriseConnector
{
	/**
	 * The job handler (server plug-in connector) tells the core server how to the job must be handled.
	 * The Id, JobType and ServerType are overruled by the core and not be changed.
	 * Other properties can be set and are configurable by system admin users.
	 * Called by BizServerJob when the Health Check or Server Job admin pages are run. 
	 *
	 * @param ServerJobConfig $jobConfig Configuration to update by the handler.
	 */
	public function getJobConfig( ServerJobConfig $jobConfig ) 
	{
		$jobConfig->NumberOfAttempts  = 5;
		$jobConfig->SysAdmin          = true;  // use acting user
		$jobConfig->Recurring         = false;
		$jobConfig->Active            = true;
		$jobConfig->UserId            = 0;     // use acting user
		$jobConfig->UserConfigNeeded  = false; // use acting user
		$jobConfig->WorkingDays       = false; // no meaning since non-recurring
		$jobConfig->JobType           = 'MSLPresentationToImageConverter';			
	}

	/**
	 * Called by BizServerJob when a server job is picked up from the queue
	 * and needs to be run by the job handler implementing this interface.
	 * The handler should update the status through $job->JobStatus->setStatus().
	 * See estimatedLifeTime() when your job may have long execution times.
	 *
	 * @param ServerJob $job
	 */
	public function runJob( ServerJob $job ) 
	{
		try {

			self::unserializeJobFieldsValue( $job );

			$ticket   = BizSession::getTicket();
			$userName = BizSession::getShortUserName();
			$jobData  = $job->JobData[0];	

			if ( !isset( $jobData['presentationid'] ) || !is_numeric ( $jobData['presentationid'] ) ) {
				$message = 'Invalid Presentation Id';
				throw new BizException( '', 'Client', '', $message );
			}

			if ( !isset( $jobData['dossierid'] ) || !is_numeric ( $jobData['dossierid'] ) ) {
				$message = 'Invalid Dossier Id';
				throw new BizException( '', 'Client', '', $message );
			}

			$presentationId = $jobData['presentationid'];
			$dossierId      = $jobData['dossierid'];



			// Get the presentation object

			$presentationObject       = BizObject::getObject( $presentationId, $userName, false, 'native');
			$presentationName         = $presentationObject->MetaData->BasicMetaData->Name;
			$presentationBrandId      = $presentationObject->MetaData->BasicMetaData->Publication->Id;
			$presentationBrandName    = $presentationObject->MetaData->BasicMetaData->Publication->Name;
			$presentationCategoryId   = $presentationObject->MetaData->BasicMetaData->Category->Id;
			$presentationCategoryName = $presentationObject->MetaData->BasicMetaData->Category->Name;
			$presentationIssueId      = $presentationObject->MetaData->BasicMetaData->Category->Name;
			$presentationExt          = MimeTypeHandler::mimeType2FileExt( $presentationObject->MetaData->ContentMetaData->Format, $presentationObject->MetaData->BasicMetaData->Type );
			$presentationData         = file_get_contents( $presentationObject->Files[0]->FilePath );

			$imageStateId   = 0;
			$imageStateName = MSLP2IC_IMAGE_STATE;
			$pubImageStates = BizWorkflow::getStates( $userName, $presentationBrandId, null, null, 'Image' );

			foreach ( $pubImageStates as $pubImageState ) {
				if ( strtolower( $pubImageState->Name ) == strtolower( $imageStateName ) ) {
					$imageStateId = $pubImageState->Id;
				}
			}

			if ( $imageStateId === 0 ) {
				throw new BizException( '', 'Client', '', 'Invalid Image State' );
			}

			file_put_contents('$pubImageStates.txt', print_r($pubImageStates, true));

			// Create the individual PNG files from each slide

		    $presentationImages = self::createImagesFromPresentation( $presentationId, $presentationExt, $presentationData );

		    // Create new image objects for each of the PNGs

			$tmpDir = TEMPDIRECTORY . '/' . $presentationId . '/';

			$i = 0;

			foreach ($presentationImages as $presentationImage ) {

				$i++;

				// Get the image data

				$imageData = file_get_contents( $tmpDir . $presentationImage );

				$attachment     = new Attachment( 'native', 'image/png' );
				$transferServer = new BizTransferServer();
				$transferServer->writeContentToFileTransferServer( $imageData, $attachment );
	
				$files   = array();
				$files[] = $attachment;

				$imageObject                                             = new Object();
		        $imageObject->MetaData                                   = new MetaData();
		        $imageObject->MetaData->BasicMetaData                    = new BasicMetaData();
		        $imageObject->MetaData->BasicMetaData->Name              = $presentationName . '_slide_' . $i . '_of_' . count( $presentationImages );
		        $imageObject->MetaData->BasicMetaData->Type              = 'Image';
				$imageObject->MetaData->ContentMetaData                  = new ContentMetaData();
		        $imageObject->MetaData->ContentMetaData->Format          = 'image/png';
		        $imageObject->MetaData->ContentMetaData->FileSize        = strlen( $imageData );
		        $imageObject->MetaData->BasicMetaData->Publication       = new Publication();
		        $imageObject->MetaData->BasicMetaData->Publication->Id   = $presentationBrandId;
		        $imageObject->MetaData->BasicMetaData->Publication->Name = $presentationBrandName;
		        $imageObject->MetaData->BasicMetaData->Category          = new Category();
		        $imageObject->MetaData->BasicMetaData->Category->Id      = $presentationCategoryId;
		        $imageObject->MetaData->BasicMetaData->Category->Name    = $presentationCategoryName;
		        $imageObject->MetaData->WorkflowMetaData                 = new WorkflowMetaData();
		        $imageObject->MetaData->WorkflowMetaData->State          = new State();
		        $imageObject->MetaData->WorkflowMetaData->State->Id      = $imageStateId;
		        $imageObject->MetaData->WorkflowMetaData->State->Name    = $imageStateName;
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
		
					BizRelation::createObjectRelations( $newRelation, $userName, null, true );	
				}
			}

			// Remove the TMP directory

			// self::deleteTmpFiles( $tmpDir );

			$job->JobStatus->setStatus( ServerJobStatus::COMPLETED );

		}
		catch( BizException $e ) {
			if ( $e->getErrorCode() == 'S1034' ) { // ERR_NO_ACTION_TAKEN, happens when semaphore could not be created
				$job->JobStatus->setStatus( ServerJobStatus::REPLANNED );
			}
			else {

				$job->JobStatus->setStatus( ServerJobStatus::FATAL ); // give up
				$job->ErrorMessage = $e->getMessage();
			}
		}
	}
	/**
	 * Called by the job processor (in background) when the job is picked from the queue to
	 * initialise the job before it gets processed through {@link:runJob()}.
	 *
	 * For each job, this function is called once a lifetime to let the connector gather additional
	 * information and e.g. enrich $job->JobData with that. When the job fails, the data is preserved.
	 * And so, on a retry this function is not(!) called again. When the whole job type is put on hold
	 * (see {@link:replanJobType()}), the {@link:beforeRunJob()} function is still called to avoid a big
	 * gap between the job creation (pushed in queue) time and the initialization time.
	 *
	 * When this function is executed, job processor will set the status of this job from 'Busy' to 'Initialized'.
	 * However, this can be overridden by setting the status in this function. In other words, as long as the
	 * status is set to 'Busy', the job processor will set it to 'Initialized', otherwise the job processor
	 * will respect the status set by this function.
	 * This is convenient when, for instance, during the run of this function, it encounters some error, and so
	 * there's no point to continue with the next stage {@link:runJob()}. Function can then choose to set to
	 * 'COMPLETED' OR 'FATAL' so that the task will not be processed anymore. In this case, the job processor will
	 * respect the 'COMPLETED' OR 'FATAL' instead of setting it to 'INITIALIZED'.
	 *
	 * @since 9.4
	 * @param ServerJob $job
	 */
	public function beforeRunJob( ServerJob $job ) 
	{
	}

	/**
	 * Called by BizServerJob when a scheduled job needs to be created.
	 *
	 * @since v8.3
	 * @param bool $pushIntoQueue True to compose the job and push into job queue. False to just return the composed job object.
	 * @return ServerJob|Null Job that has been created | Null (default) when no job is created.
	 */
	public function createJob( $pushIntoQueue ) 
	{
		return null;
	}

	/**
	 * Called by the job processor (in background) when the server plugin connector has set a server
	 * job status to REPLANNED or ERROR through the {@link:runJob()} function to find out how long a
	 * failing job type needs to be put on hold.
	 *
	 * When a positive number is returned, not only the given job is put on hold, but all jobs of
	 * that type are no longer processed. The number represents the seconds to wait before the core
	 * will retry the first job (of that type) in the queue. The processing sequence is FIFO.
	 * When NULL is returned, the job will be retried again (soon after), and other jobs (of that type)
	 * will be (re)tried as well, including new jobs (of that type) that are pushed into the queue.
	 * By default, job types are put on hold for one minute (60 seconds).
	 *
	 * @since 9.4
	 * @param ServerJob $job
	 * @return integer|null Seconds to put the job type on hold. NULL to continue processing.
	 */
	public function replanJobType( ServerJob $job ) 
	{
		return 60;
	}

	/**
	 * Estimates the life time of a given job. Once expired, job status PROGRESS will change into GAVE UP.
	 *
	 * The job processor needs to have a rough idea how long your job is gonna run.
	 * The real execution time should not exceed the given estimation (number of seconds). 
	 * This enables the processor to detect jobs that are running forever or jobs that have 
	 * been crashed unexpectedly (e.g. too much memory consumption, etc).
	 *
	 * For example, a co-worker has 3 the same Crontab configurations like this:
	 *    curl "http://127.0.0.1/Enterprise/jobindex.php?maxexectime=60&maxjobprocesses=3"
	 * That results into 3 job processors running in parallel, all picking jobs from the queue.
	 * When a processor has completed one job, it will check the given maxexectime against
	 * its execution time. When there is time left, it takes another job, else it bails out.
	 *
	 * The Crontab is not aware of all this and simply starts 3 job processors every minute.
	 * When a processor detects there are 3 jobs running on this co-worker already, it bails out.
	 * In other terms, where there are 3 jobs with status 'Busy', no more jobs will be picked.
	 * When there is a problematic job implementation that crashes often, it would entirely
	 * block the co-worker from picking up any jobs, forever! To avoid this from happening, 
	 * jobs should either [1] run a short time (< 5 minutes) or [2] tell the processor that 
	 * they are alife and truly busy processing:
	 *
	 * [1] To run a short time, you may consider splitting up your job into many jobs. But,
	 * only do when you can think of atomic steps since jobs can be processed in random order. 
	 * 
	 * [2] To tell the processor that the job is still alife and processing, the runJob()
	 * should refresh the semaphore that is created by the processor. This can be done
	 * as follows:
	 *		require_once BASEDIR.'/server/bizclasses/BizSemaphore.class.php';
	 *		require_once BASEDIR.'/server/bizclasses/BizServerJob.class.php';
	 *		$bizServerJob = new BizServerJob();
	 *		$semaName = $bizServerJob->composeSemaphoreNameForJobId( $job->JobId );
	 *		while( ... [busy] ... ) {
	 *			... [process job] ...
	 *			BizSemaphore::refreshSemaphoreByEntityId( $semaName );
	 *		}
	 * Note that '[process jobs]' should never to hang and should return periodically.
	 *
	 * A good practise is to let estimatedLifeTime() return a small number but to make sure
	 * that the semaphore is refreshed within that time. And, not to refresh too often,
	 * to avoid stessing the database, since the semaphore is implemented in the database.
	 * 
	 * Let's take an example. When you think your job normally returns within 3 minutes, 
	 * simply let it return 3x60=180 seconds. Refresh the semaphore e.g. every 15 seconds.
	 *
	 * In case your job does up-/download potentially large files, you can hook into the cURL
	 * adapter and monitor progress which enables you to refresh the semaphore. Doing so, you
	 * should realize that the callback of this adapter happens far too often (every few ms).
	 * Better is to ignore these iterations until you have reached 15 seconds, then update
	 * the semaphore. 
	 *
	 * When the job exceeds the estimated execution time, the job processor will let it
	 * run. However, the job status will then be set to 'Gave Up'. That will trigger the
	 * job processor to pickup the next job. 
	 *
	 * @since 9.6.0
	 * @param ServerJob $job
	 * @return integer Seconds needed to run the job.
	 */
	public function estimatedLifeTime( ServerJob $job ) 
	{
		return 3600;
	}

	public function getPrio() { return self::PRIO_DEFAULT; }

		/**
	 * Prepare ServerJob (parameter $job) to be ready for use by the caller.
	 *
	 * The parameter $job is returned from database as it is (i.e some data might be
	 * serialized for DB storage purposes ), this function make sure all the data are
	 * un-serialized.
	 * Mainly called when ServerJob Object is passed from functions in BizServerJob class.
	 *
	 * @param ServerJob $job
	 */
	private static function unserializeJobFieldsValue( ServerJob $job )
	{
		// Make sure to include the necessary class file(s) here, else it will result into
		// 'PHP_Incomplete_Class Object' during unserialize.
		require_once BASEDIR.'/server/interfaces/services/wfl/DataClasses.php';
		if( !is_null( $job->JobData ) ) {
			$job->JobData = unserialize( $job->JobData );
		}
	}

	/**
	 * Make sure the parameter $job passed in is ready for used by database.
	 *
	 * Mainly called when ServerJob Object needs to be passed to functions in BizServerJob class.
	 *
	 * @param ServerJob $job
	 */
	private static function serializeJobFieldsValue( ServerJob $job )
	{
		if( !is_null( $job->JobData ) ) {
			$job->JobData = serialize( $job->JobData );
		}
	}

	/**
     * Gets the object from the Id
     *
     * @return OBJ $object
     */
    private static function getObjectFromId( $objectId, $userName = '' ) {

        try {

            if ( $userName == '' ) {
                $userName = BizSession::getShortUserName();
            }
            return BizObject::getObject( $objectId, $userName, false, 'none', null, null, true );
        }
        catch( BizException $e ) {
            // Do nothing
        }
    }

	private static function createImagesFromPresentation( $presentationId, $presentationExt, $presentationData ) {

		// 1. Create the temporary file for the presentation

		$tmpDir            = TEMPDIRECTORY  . '/' . $presentationId;
		$baseFileName      = $tmpDir . '/'  . $presentationId;
		$inputFileName     = $baseFileName  . $presentationExt;

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

	/* 
	 * php delete function that deals with directories recursively
	 */
	private static function deleteTmpFiles( $target ) {
	    if( is_dir( $target ) ) {
	        $files = glob( $target . '*', GLOB_MARK ); //GLOB_MARK adds a slash to directories returned
			foreach( $files as $file ) {
	            deleteTmpFiles( $file );      
	        }
			rmdir( $target );
	    }
	    elseif( is_file( $target ) ) {
	        unlink( $target );  
	    }
	}
}
