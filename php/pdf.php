<?php
namespace SIM\USERMANAGEMENT;
use SIM;

add_filter('sim_before_pdf_text', __NAMESPACE__.'\beforePdfText', 10, 3);
function beforePdfText($cellText, $pdf){
    //text contains a filepath
    if(is_array($cellText) && isset($cellText['picture'])){
        $cellText	= $cellText['name'];

        $length     = $pdf->GetStringWidth($cellText);

        // we need to have at least 6 units free at the left
        if($length > 17){
            // available space at the left
            $remaining  = (30 - $length) / 2;

            $prepend    = str_repeat(" ", max(0, ceil((6 - $remaining) / 0.392285)));

            $cellText = $prepend.$cellText; // each space is 0.78457 units
        }
    }

    return $cellText;
}

add_action('sim_after_pdf_text', __NAMESPACE__.'\afterPdfText', 10, 6);
function afterPdfText($cellText, $pdf, $x, $y, $cellWidth, $reset){
    if(is_array($cellText) && isset($cellText['picture'])){
        $filePath	= $cellText['picture'];
        
        $pdf->addCellPicture($filePath, $x, $y, '', 6, $reset);
    }

}