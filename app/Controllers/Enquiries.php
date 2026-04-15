<?php

namespace App\Controllers;

class Enquiries extends BaseController
{
    public function index(): string
    {
        $tenantId = (int) session()->get('tenant_id');
        $masterData = service('masterData');

        return view('enquiries/index', $this->buildShellViewData([
            'title'          => 'Enquiries',
            'pageTitle'      => 'Enquiries',
            'activeNav'      => 'enquiries',
            'sources'        => $masterData->getEffectiveValues('enquiry_source', $tenantId),
            'qualifications' => $masterData->getEffectiveValues('lead_qualification', $tenantId),
            'followups'      => $masterData->getEffectiveValues('followup_status', $tenantId),
            'modes'          => $masterData->getEffectiveValues('mode_of_communication', $tenantId),
            'lostReasons'    => $masterData->getEffectiveValues('enquiry_lost_reason', $tenantId),
            'closureReasons' => $masterData->getEffectiveValues('enquiry_closure_reason', $tenantId),
            'purposes'       => $masterData->getEffectiveValues('purpose_category', $tenantId),
            'courses'        => $masterData->getEffectiveValues('course', $tenantId),
        ]));
    }
}
