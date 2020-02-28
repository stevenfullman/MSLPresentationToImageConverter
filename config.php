<?php

// If true, then only presentations directly uploaded into an existing dossier will have
// their individual slides converted into images
// The converted images will be added to the same dossier as the original presentation
//
// If false, then all uploaded/created presentations will have their individual slides
// converted into images, even if the original presentation is not uploaded into a dossier. 
// If the original presentation is in a dossier, then the individually created images
// will be added to the same dossier									
									
define( 'MSLP2IC_ONLY_EXEC_IF_CREATED_IN_DOSSIER', true );

define( 'MSLP2IC_IMAGE_STATE', 'Image Draft' );


