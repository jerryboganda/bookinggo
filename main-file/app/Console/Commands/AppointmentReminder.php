<?php

namespace App\Console\Commands;

use App\Events\AppointmentReminder as EventsAppointmentReminder;
use Illuminate\Console\Command;
use App\Models\Business;
use App\Models\EmailTemplate;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AppointmentReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:appointment-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $businesses = Business::all();
        foreach($businesses as $business)
        {

            $company_settings = getCompanyAllSetting($business->created_by,$business->id);

            $timezone = $company_settings['defult_timezone'];
            $reminder = $company_settings['reminder_interval'] ?? null;

            if(!empty($reminder))
            {
                $date = Carbon::now()->timezone($timezone)->format('d-m-Y');
                $time = Carbon::now()->timezone($timezone)->format('H:i');

                $appointments = Appointment::where('business_id', $business->id)
                                ->orderBy('date','ASC')
                                ->whereRaw("STR_TO_DATE(date, '%d-%m-%Y') >= CURDATE()") // Get appointments for today or later
                                ->get();

                foreach ($appointments as $appointment)
                {
                    $startTime = explode('-', $appointment->time)[0];
                    $appointment_date = $appointment->date;

                    $appointmentDateTime = Carbon::createFromFormat('d-m-Y H:i', $appointment_date . ' ' . $startTime);

                    $reminderDateTime = $appointmentDateTime->subMinutes($reminder);

                    $reminderdate = $reminderDateTime->format('d-m-Y');
                    $remindertime = $reminderDateTime->format('H:i');

                    if($appointment_date == $date && $time == $remindertime)
                    {
                        $appointment_number = Appointment::appointmentNumberFormat($appointment->id,$appointment->created_by,$appointment->business_id);
                        if( (!empty($company_settings['Appointment Reminder']) && $company_settings['Appointment Reminder']  == true ))
                        {
                            $uArr = [
                                'company_name'=> $appointment->business->name ?? '',
                                'customer'=>$appointment->CustomerData ? $appointment->CustomerData->name : $appointment->name,
                                'service'=>$appointment->ServiceData ? $appointment->ServiceData->name : '-',
                                'location'=>$appointment->LocationData ? $appointment->LocationData->name : '-',
                                'staff'=>$appointment->StaffData->user ? $appointment->StaffData->user->name : '-',
                                'appointment_date'=>$appointment->date,
                                'appointment_time'=>$appointment->time,
                                'appointment_number'=>$appointment_number,
                            ];

                            $resp = EmailTemplate::sendEmailTemplate('Appointment Reminder', [$appointment->CustomerData ? $appointment->CustomerData->customer->email : $appointment->email], $uArr,$appointment->created_by, $appointment->business_id);

                            if(!empty($resp) && $resp['is_success'] == false && !empty($resp['error']))
                            {
                                Log::channel('reminder_log')->info($resp['error']);
                            }
                            else
                            {
                                Log::channel('reminder_log')->info("success");
                            }

                        }
                        event(new EventsAppointmentReminder($appointment));

                    }

                }

            }
            else
            {
                Log::channel('reminder_log')->info("Reminder time not set!");
            }
        }
    }
}
