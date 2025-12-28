<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\CommonEmailTemplate;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'from',
        'module_name',
        'created_by',
        'business_id',
    ];

    public static function sendEmailTemplate($emailTemplate, $mailTo, $obj, $user_id = null, $business_id = null)
    {

        if (!empty($user_id)) {
            $usr = User::where('id', $user_id)->first();
        } else {
            $usr = Auth::user();
        }

        // unset($mailTo[$usr->id]);
        //Remove Current Login user Email don't send mail to them

        $mailTo = array_values($mailTo);

        // if($usr->type != 'super admin')
        // {

        // find template is exist or not in our record
        $template = EmailTemplate::where('name', $emailTemplate)->first();

        if (isset($template) && !empty($template)) {
            // get email content language base
            $content = EmailTemplateLang::where('parent_id', '=', $template->id)->where('lang', 'LIKE', $usr->lang)->first();

            $content->from = $template->from;

            if (!empty($content->content)) {
                $content->content = self::replaceVariable($content->content, $obj);
                // send email
                if (!empty(company_setting('mail_from_address', $user_id, $business_id))) {

                    if (!empty($user_id) && empty($business_id)) {
                        $setconfing =  SetConfigEmail($user_id);
                    } elseif (!empty($user_id) && !empty($business_id)) {
                        $setconfing =  SetConfigEmail($user_id, $business_id);
                    } else {
                        $setconfing =  SetConfigEmail();
                    }
                    if ($setconfing ==  true) {
                        try {
                            Mail::to($mailTo)->send(new CommonEmailTemplate($content, $user_id, $business_id));
                        } catch (\Exception $e) {
                            $error = $e->getMessage();
                        }
                    } else {
                        $error = __('Something went wrong please try again ');
                    }
                } else {
                    $error = __('E-Mail has been not sent due to SMTP configuration');
                }

                if (isset($error)) {
                    $arReturn = [
                        'is_success' => false,
                        'error' => $error,
                    ];
                } else {
                    $arReturn = [
                        'is_success' => true,
                        'error' => false,
                    ];
                }
            } else {
                $arReturn = [
                    'is_success' => false,
                    'error' => __('Mail not send, email is empty'),
                ];
            }
            return $arReturn;
        } else {
            return [
                'is_success' => false,
                'error' => __('Mail not send, email not found'),
            ];
        }
        // }
    }

    public static function replaceVariable($content, $obj)
    {
        $arrVariable = [
            '{app_name}',
            '{app_url}',
            '{company_name}',

            '{email}',
            '{password}',

            '{staff}',
            '{service}',
            '{location}',
            '{appointment_date}',
            '{appointment_time}',
            '{appointment_number}',
            '{customer}',
            '{appointment_review}',
            '{review_url}',
            '{staff_name}',
            '{business_name}',
            '{service_name}',
            '{zoom_meeting_link}',

            '{ticket_name}',
            '{ticket_id}',
            '{reply_description}',
            '{ticket_url}',

            '{google_meet_link}',
            '{tracking_url}'
        ];
        $arrValue    = [
            'app_name' => '-',
            'app_url' => '-',
            'company_name' => '-',
            'email' => '-',
            'password' => '-',

            'staff' => '-',
            'service' => '-',
            'location' => '-',
            'appointment_date' => '-',
            'appointment_time' => '-',
            'appointment_number' => '-',
            'customer' => '-',
            'appointment_review' => '-',
            'review_url' => '-',
            'staff_name' => '-',
            'business_name' => '-',
            'service_name' => '-',
            'zoom_meeting_link' => '-',

            'ticket_name' => '-',
            'ticket_id' => '-',
            'reply_description' => '-',
            'ticket_url' => '-',

            'google_meet_link' => '-',
            'tracking_url' => '-'

        ];
        foreach ($obj as $key => $val) {
            $arrValue[$key] = $val;
        }
        $arrValue['app_name']     = env('APP_NAME');
        // $arrValue['company_name'] = '--';
        $arrValue['app_url']      = '<a href="' . env('APP_URL') . '" target="_blank">' . env('APP_URL') . '</a>';
        return str_replace($arrVariable, array_values($arrValue), $content);
    }
}
