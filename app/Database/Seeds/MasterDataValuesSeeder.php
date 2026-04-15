<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MasterDataValuesSeeder extends Seeder
{
    public function run()
    {
        $catalog = [
            'enquiry_source' => [
                ['code' => 'walk_in', 'label' => 'Walk-in', 'sort_order' => 10],
                ['code' => 'website', 'label' => 'Website', 'sort_order' => 20],
                ['code' => 'facebook', 'label' => 'Facebook', 'sort_order' => 30],
                ['code' => 'google_ads', 'label' => 'Google Ads', 'sort_order' => 40],
                ['code' => 'referral', 'label' => 'Referral', 'sort_order' => 50],
                ['code' => 'whatsapp', 'label' => 'WhatsApp', 'sort_order' => 60],
                ['code' => 'phone_call', 'label' => 'Phone Call', 'sort_order' => 70],
                ['code' => 'webinar', 'label' => 'Webinar', 'sort_order' => 80],
                ['code' => 'event', 'label' => 'Event', 'sort_order' => 90],
            ],
            'lead_qualification' => [
                ['code' => 'hot', 'label' => 'Hot', 'sort_order' => 10],
                ['code' => 'warm', 'label' => 'Warm', 'sort_order' => 20],
                ['code' => 'cold', 'label' => 'Cold', 'sort_order' => 30],
                ['code' => 'not_interested', 'label' => 'Not Interested', 'sort_order' => 40],
                ['code' => 'follow_up_later', 'label' => 'Follow Up Later', 'sort_order' => 50],
            ],
            'followup_status' => [
                ['code' => 'connected', 'label' => 'Connected', 'sort_order' => 10],
                ['code' => 'not_connected', 'label' => 'Not Connected', 'sort_order' => 20],
                ['code' => 'callback_requested', 'label' => 'Callback Requested', 'sort_order' => 30],
                ['code' => 'interested', 'label' => 'Interested', 'sort_order' => 40],
                ['code' => 'follow_up_required', 'label' => 'Follow Up Required', 'sort_order' => 50],
                ['code' => 'visit_scheduled', 'label' => 'Visit Scheduled', 'sort_order' => 60],
            ],
            'mode_of_communication' => [
                ['code' => 'phone_call', 'label' => 'Phone Call', 'sort_order' => 10],
                ['code' => 'whatsapp', 'label' => 'WhatsApp', 'sort_order' => 20],
                ['code' => 'email', 'label' => 'Email', 'sort_order' => 30],
                ['code' => 'sms', 'label' => 'SMS', 'sort_order' => 40],
                ['code' => 'in_person', 'label' => 'In Person', 'sort_order' => 50],
                ['code' => 'video_call', 'label' => 'Video Call', 'sort_order' => 60],
            ],
            'enquiry_lost_reason' => [
                ['code' => 'not_interested', 'label' => 'Not Interested', 'sort_order' => 10],
                ['code' => 'budget_issue', 'label' => 'Budget Issue', 'sort_order' => 20],
                ['code' => 'joined_other_institute', 'label' => 'Joined Other Institute', 'sort_order' => 30],
                ['code' => 'no_response', 'label' => 'No Response', 'sort_order' => 40],
                ['code' => 'location_issue', 'label' => 'Location Issue', 'sort_order' => 50],
                ['code' => 'timing_issue', 'label' => 'Timing Issue', 'sort_order' => 60],
            ],
            'enquiry_closure_reason' => [
                ['code' => 'converted_to_admission', 'label' => 'Converted to Admission', 'sort_order' => 10],
                ['code' => 'duplicate', 'label' => 'Duplicate', 'sort_order' => 20],
                ['code' => 'expired', 'label' => 'Expired', 'sort_order' => 30],
                ['code' => 'manually_closed', 'label' => 'Manually Closed', 'sort_order' => 40],
            ],
            'purpose_category' => [
                ['code' => 'course_enquiry', 'label' => 'Course Enquiry', 'sort_order' => 10],
                ['code' => 'demo_request', 'label' => 'Demo Request', 'sort_order' => 20],
                ['code' => 'fee_enquiry', 'label' => 'Fee Enquiry', 'sort_order' => 30],
                ['code' => 'support_request', 'label' => 'Support Request', 'sort_order' => 40],
            ],
            'course' => [
                ['code' => 'java_full_stack', 'label' => 'Java Full Stack', 'sort_order' => 10, 'metadata_json' => '{"duration_days":180,"delivery_mode":"hybrid","active_for_enquiry":true,"active_for_admission":true}'],
                ['code' => 'python_full_stack', 'label' => 'Python Full Stack', 'sort_order' => 20, 'metadata_json' => '{"duration_days":180,"delivery_mode":"hybrid","active_for_enquiry":true,"active_for_admission":true}'],
                ['code' => 'data_analytics', 'label' => 'Data Analytics', 'sort_order' => 30, 'metadata_json' => '{"duration_days":150,"delivery_mode":"hybrid","active_for_enquiry":true,"active_for_admission":true}'],
                ['code' => 'software_testing', 'label' => 'Software Testing', 'sort_order' => 40, 'metadata_json' => '{"duration_days":120,"delivery_mode":"offline","active_for_enquiry":true,"active_for_admission":true}'],
                ['code' => 'aws_devops', 'label' => 'AWS DevOps', 'sort_order' => 50, 'metadata_json' => '{"duration_days":120,"delivery_mode":"online","active_for_enquiry":true,"active_for_admission":true}'],
            ],
        ];

        foreach ($catalog as $typeCode => $values) {
            $type = $this->db->table('master_data_types')->where('code', $typeCode)->get()->getRow();
            if (! $type) {
                continue;
            }

            foreach ($values as $value) {
                $existing = $this->db->table('master_data_values')
                                     ->where('type_id', $type->id)
                                     ->where('scope_type', 'platform')
                                     ->where('tenant_id', null)
                                     ->where('code', $value['code'])
                                     ->get()
                                     ->getRow();

                $payload = $value + [
                    'type_id'     => $type->id,
                    'scope_type'  => 'platform',
                    'tenant_id'   => null,
                    'is_system'   => 1,
                    'status'      => 'active',
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ];

                if ($existing) {
                    $this->db->table('master_data_values')
                             ->where('id', $existing->id)
                             ->update($payload);
                    continue;
                }

                $this->db->table('master_data_values')->insert($payload);
            }
        }
    }
}
