<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Mail\TestMail;
use App\Models\ApikeySetiings;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($settings)
    {
        $file_type = config('files_types');
        $timezones = config('timezones');


        return view('super-admin.settings.index', compact('settings', 'file_type', 'timezones'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // EMERGENCY DEBUG - CHECK IF WE EVEN GET HERE
        file_put_contents(storage_path('logs/emergency_debug.log'), 
            "\n\n" . date('Y-m-d H:i:s') . " - STORE METHOD HIT!\n" .
            "User ID: " . Auth::user()->id . "\n" .
            "Request Data: " . json_encode($request->except(['_token', 'logo_dark', 'logo_light', 'favicon'])) . "\n",
            FILE_APPEND
        );
        
        // COMPREHENSIVE DEBUG LOGGING
        \Log::info('=== SETTINGS SAVE START ===');
        \Log::info('Request Data: ' . json_encode($request->except(['_token', '_method', 'logo_dark', 'logo_light', 'favicon'])));
        \Log::info('User ID: ' . Auth::user()->id);
        \Log::info('User Type: ' . Auth::user()->type);
        \Log::info('User Email: ' . Auth::user()->email);
        \Log::info('creatorId(): ' . creatorId());
        \Log::info('getActiveBusiness(): ' . getActiveBusiness());
        
        // Check permission
        if (!Auth::user()->isAbleTo('setting manage')) {
            \Log::error('Permission denied for user: ' . Auth::user()->id);
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        
        \Log::info('Permission check passed');
        
        $post = $request->all();
        unset($post['_token']);
        unset($post['_method']);


            if (!isset($post['landing_page'])) {
                $post['landing_page'] = 'off';
            }

            if (!isset($post['site_rtl'])) {
                $post['site_rtl'] = 'off';
            }
            if (!isset($post['signup'])) {
                $post['signup'] = 'off';
            }
            if (!isset($post['email_verification'])) {
                $post['email_verification'] = 'off';
            }
            if (!isset($post['site_transparent'])) {
                $post['site_transparent'] = 'off';
            }
            if (!isset($post['cust_darklayout'])) {
                $post['cust_darklayout'] = 'off';
            }
            if (isset($request->color) && $request->color_flag == 'false') {
                $post['color'] = $request->color;
            } else {
                $post['color'] = $request->custom_color;
            }
            unset($post['custom_color']);
            
            // DELETE ALL DUPLICATES FIRST - BEFORE processing file uploads
            \Log::info('Removing duplicate settings before processing files...');
            $allExisting = Setting::where('business', 0)->where('created_by', Auth::user()->id)->get();
            $grouped = $allExisting->groupBy('key');
            $duplicatesDeleted = 0;
            foreach ($grouped as $key => $settingsGroup) {
                if ($settingsGroup->count() > 1) {
                    // Keep the most recently updated, delete the rest
                    $keep = $settingsGroup->sortByDesc('updated_at')->first();
                    foreach ($settingsGroup->where('id', '!=', $keep->id) as $duplicate) {
                        $duplicate->delete();
                        $duplicatesDeleted++;
                    }
                }
            }
            \Log::info("Deleted $duplicatesDeleted duplicate settings before file processing");
            
            // Clear cache so getAdminAllSetting() gets fresh data without duplicates
            Cache::forget('admin_settings');
            AdminSettingCacheForget();
            \Log::info('Cache cleared before fetching admin settings for file upload');
            
            $admin_settings = getAdminAllSetting();
            \Log::info('Admin settings fetched - logo_dark: ' . ($admin_settings['logo_dark'] ?? 'NOT SET'));
            \Log::info('Admin settings fetched - logo_light: ' . ($admin_settings['logo_light'] ?? 'NOT SET'));
            \Log::info('Admin settings fetched - favicon: ' . ($admin_settings['favicon'] ?? 'NOT SET'));
            
            if ($request->hasFile('logo_dark')) {
                \Log::info('=== PROCESSING logo_dark UPLOAD ===');
                $logo_dark = 'logo_dark.png';
                $uplaod = upload_file($request, 'logo_dark', $logo_dark, 'logo');

                $logo_dark =  'logo_dark_' . time() . '.png';
                \Log::info('Uploading logo_dark with filename: ' . $logo_dark);
                $uplaod = upload_file($request, 'logo_dark', $logo_dark, 'logo');
                if ($uplaod['flag'] == 1) {
                    $post['logo_dark'] = $uplaod['url'];
                    \Log::info('logo_dark uploaded successfully to: ' . $uplaod['url']);
                    \Log::info('logo_dark uploaded successfully to: ' . $uplaod['url']);

                    $old_logo_dark = isset($admin_settings['logo_dark']) ? $admin_settings['logo_dark'] : null;
                    \Log::info('Old logo_dark path: ' . ($old_logo_dark ?? 'NULL'));
                    if (!empty($old_logo_dark) && check_file($old_logo_dark)) {
                        delete_file($old_logo_dark);
                        \Log::info('Deleted old logo_dark file');
                    }
                } else {
                    \Log::error('logo_dark upload failed: ' . $uplaod['msg']);
                    return redirect()->back()->with('error', $uplaod['msg']);
                }
            }
            if ($request->hasFile('logo_light')) {
                \Log::info('=== PROCESSING logo_light UPLOAD ===');

                $logo_light = 'logo_light.png';
                $uplaod = upload_file($request, 'logo_light', $logo_light, 'logo');

                $logo_light =  'logo_light_' . time() . '.png';
                \Log::info('Uploading logo_light with filename: ' . $logo_light);
                $uplaod = upload_file($request, 'logo_light', $logo_light, 'logo');
                if ($uplaod['flag'] == 1) {
                    $post['logo_light'] = $uplaod['url'];
                    \Log::info('logo_light uploaded successfully to: ' . $uplaod['url']);

                    $old_logo_light = isset($admin_settings['logo_light']) ? $admin_settings['logo_light'] : null;
                    \Log::info('Old logo_light path: ' . ($old_logo_light ?? 'NULL'));
                    if (!empty($old_logo_light) && check_file($old_logo_light)) {
                        delete_file($old_logo_light);
                        \Log::info('Deleted old logo_light file');
                    }
                } else {
                    \Log::error('logo_light upload failed: ' . $uplaod['msg']);
                    return redirect()->back()->with('error', $uplaod['msg']);
                }
            }
            if ($request->hasFile('favicon')) {
                \Log::info('=== PROCESSING favicon UPLOAD ===');

                $favicon = 'favicon.png';
                $uplaod = upload_file($request, 'favicon', $favicon, 'logo');

                $favicon =  'favicon_' . time() . '.png';
                \Log::info('Uploading favicon with filename: ' . $favicon);
                $uplaod = upload_file($request, 'favicon', $favicon, 'logo');
                if ($uplaod['flag'] == 1) {
                    $post['favicon'] = $uplaod['url'];
                    \Log::info('favicon uploaded successfully to: ' . $uplaod['url']);

                    $old_favicon = isset($admin_settings['favicon']) ? $admin_settings['favicon'] : null;
                    \Log::info('Old favicon path: ' . ($old_favicon ?? 'NULL'));
                    if (!empty($old_favicon) && check_file($old_favicon)) {
                        delete_file($old_favicon);
                        \Log::info('Deleted old favicon file');
                    }
                } else {
                    \Log::error('favicon upload failed: ' . $uplaod['msg']);
                    return redirect()->back()->with('error', $uplaod['msg']);
                }
            }
            
            \Log::info('=== FILE UPLOADS COMPLETE ===');
            \Log::info('POST data contains: ' . json_encode(array_keys($post)));
            if (isset($post['logo_dark'])) \Log::info('POST logo_dark = ' . $post['logo_dark']);
            if (isset($post['logo_light'])) \Log::info('POST logo_light = ' . $post['logo_light']);
            if (isset($post['favicon'])) \Log::info('POST favicon = ' . $post['favicon']);
            
            // CRITICAL FIX: Preserve existing logo values if they weren't uploaded in this request
            // This prevents logo settings from being deleted when user saves other settings
            $logoKeys = ['logo_dark', 'logo_light', 'favicon'];
            foreach ($logoKeys as $logoKey) {
                if (!isset($post[$logoKey]) && !empty($admin_settings[$logoKey])) {
                    $post[$logoKey] = $admin_settings[$logoKey];
                    \Log::info("Preserving existing $logoKey value: " . $admin_settings[$logoKey]);
                }
            }



            \Log::info('Starting to save ' . count($post) . ' settings');
            \Log::info('Business ID will be: 0');
            \Log::info('creatorId() returns: ' . creatorId());
            \Log::info('Auth::user()->id is: ' . Auth::user()->id);
            \Log::info('Auth::user()->type is: ' . Auth::user()->type);
            
            // Check for any new duplicates that might have been created
            \Log::info('Final duplicate check before main save...');
            $finalExisting = Setting::where('business', 0)->where('created_by', Auth::user()->id)->get();
            $finalGrouped = $finalExisting->groupBy('key');
            $finalDuplicatesDeleted = 0;
            foreach ($finalGrouped as $key => $settingsGroup) {
                if ($settingsGroup->count() > 1) {
                    // Keep the most recently updated, delete the rest
                    $keep = $settingsGroup->sortByDesc('updated_at')->first();
                    foreach ($settingsGroup->where('id', '!=', $keep->id) as $duplicate) {
                        $duplicate->delete();
                        $finalDuplicatesDeleted++;
                    }
                }
            }
            \Log::info("Final cleanup: Deleted $finalDuplicatesDeleted duplicate settings");
            
            $savedCount = 0;
            $updatedCount = 0;
            $insertedCount = 0;
            
            foreach ($post as $key => $value) {
                // For super admin, always use business = 0 and current user's ID
                $businessId = 0;
                $createdBy = Auth::user()->id; // Use the actual logged-in user's ID directly
                
                // Define the data to be updated or inserted
                $data = [
                    'key' => $key,
                    'business' => $businessId,
                    'created_by' => $createdBy,
                ];

                \Log::info("Processing setting: $key = " . (is_array($value) ? json_encode($value) : $value));
                \Log::info("  Will use: business=$businessId, created_by=$createdBy");
                
                // Check if the record exists, and update or insert accordingly
                $existing = Setting::where(['key' => $key, 'business' => $businessId, 'created_by' => $createdBy])->first();
                if ($existing) {
                    \Log::info("  -> Updating existing setting ID: {$existing->id}");
                    if (in_array($key, ['logo_dark', 'logo_light', 'favicon'])) {
                        \Log::info("  -> OLD VALUE: " . $existing->value);
                        \Log::info("  -> NEW VALUE: " . $value);
                    }
                    $existing->value = $value;
                    $existing->updated_at = now();
                    $existing->save();
                    $updatedCount++;
                    
                    if (in_array($key, ['logo_dark', 'logo_light', 'favicon'])) {
                        // Verify it was actually saved
                        $verify = Setting::find($existing->id);
                        \Log::info("  -> VERIFIED IN DB: " . $verify->value);
                    }
                } else {
                    \Log::info("  -> Inserting new setting");
                    Setting::insert(array_merge($data, ['value' => $value, 'created_at' => now(), 'updated_at' => now()]));
                    $insertedCount++;
                }
                $savedCount++;
            }

            \Log::info("Save complete - Total: $savedCount, Updated: $updatedCount, Inserted: $insertedCount");
            
            // Clear specific cache keys
            \Log::info('Clearing cache...');
            Cache::forget('admin_settings');
            AdminSettingCacheForget();
            comapnySettingCacheForget();
            
            \Log::info('Cache cleared successfully');
            
            // Verify settings after save
            $verifySettings = Setting::where('business', 0)->where('created_by', Auth::user()->id)->count();
            \Log::info("After save - Total settings in DB with business=0 and created_by=" . Auth::user()->id . ": $verifySettings");
            
            // Verify logo settings were saved correctly
            $logoKeys = ['logo_dark', 'logo_light', 'favicon'];
            foreach ($logoKeys as $logoKey) {
                $logoSetting = Setting::where('key', $logoKey)->where('business', 0)->where('created_by', Auth::user()->id)->get();
                \Log::info("After save - $logoKey in DB: " . $logoSetting->count() . " records");
                foreach ($logoSetting as $idx => $ls) {
                    \Log::info("  [$idx] ID: {$ls->id}, Value: {$ls->value}, Updated: {$ls->updated_at}");
                }
            }
            
            // Verify specific setting was saved
            $appNameSetting = Setting::where('key', 'app_name')->where('business', 0)->where('created_by', Auth::user()->id)->first();
            \Log::info("After save - app_name in DB: " . ($appNameSetting ? $appNameSetting->value : 'NOT FOUND'));
            
            // Check what getAdminAllSetting returns NOW (should fetch fresh from DB)
            $adminSettings = getAdminAllSetting();
            \Log::info("After cache clear - getAdminAllSetting() returns " . count($adminSettings) . " settings");
            \Log::info("After cache clear - app_name from helper: " . ($adminSettings['app_name'] ?? 'NOT FOUND'));
            foreach ($logoKeys as $logoKey) {
                \Log::info("After cache clear - $logoKey from helper: " . ($adminSettings[$logoKey] ?? 'NOT FOUND'));
            }
            
            \Log::info('=== SETTINGS SAVE END ===');
            
            // Use regular session instead of flash (flash isn't persisting with database driver)
            session()->put('success_message', __('Setting save sucessfully.'));
            session()->save(); // Force save
            
            \Log::info('Session success_message set: ' . session()->get('success_message'));
            \Log::info('Session ID: ' . session()->getId());
            \Log::info('Referer: ' . request()->header('Referer'));
            \Log::info('Previous URL: ' . url()->previous());
            
            $redirectUrl = url()->previous();
            \Log::info('Will redirect to: ' . $redirectUrl);
            
            return redirect($redirectUrl);
    }
    
    public function SystemStore(Request $request)
    {
        if (Auth::user()->isAbleTo('setting manage')) {
            $post = $request->all();
            unset($post['_token']);
            unset($post['_method']);

            // For super admin, always use business = 0
            $businessId = 0;
            
            foreach ($post as $key => $value) {
                // Define the data to be updated or inserted
                $data = [
                    'key' => $key,
                    'business' => $businessId,
                    'created_by' => creatorId(),
                ];

                // Check if the record exists, and update or insert accordingly
                $existing = Setting::where(['key' => $key, 'business' => $businessId])->first();
                if ($existing) {
                    $existing->value = $value;
                    $existing->updated_at = now();
                    $existing->save();
                } else {
                    Setting::insert(array_merge($data, ['value' => $value, 'created_at' => now(), 'updated_at' => now()]));
                }
            }

            // Settings Cache forget
            AdminSettingCacheForget();
            comapnySettingCacheForget();
            
            session()->put('success_message', __('Setting save sucessfully.'));
            return redirect()->back();
        } else {
            session()->put('error_message', __('Permission denied.'));
            return redirect()->back();
        }
    }

    // currency setting store
    public function saveCurrencySettings(Request $request)
    {
        $post = $request->all();
        unset($post['_token']);
        unset($post['_method']);
        if (isset($post['defult_currancy'])) {
            $data = explode('-', $post['defult_currancy']);
            $post['defult_currancy_symbol'] = $data[0];
            $post['defult_currancy']        = $data[1];
        } else {
            $post['defult_currancy']        = 'USD';
            $post['defult_currancy_symbol'] = '$';
        }
        if (isset($post['site_currency_symbol_position'])) {
            $post['site_currency_symbol_position'] = !empty($request->site_currency_symbol_position) ? $request->site_currency_symbol_position : 'pre';
        }
        
        // For super admin, always use business = 0
        $businessId = 0;
        
        foreach ($post as $key => $value) {
            // Define the data to be updated or inserted
            $data = [
                'key' => $key,
                'business' => $businessId,
                'created_by' => creatorId(),
            ];

            // Check if the record exists, and update or insert accordingly
            $existing = Setting::where(['key' => $key, 'business' => $businessId])->first();
                if ($existing) {
                    $existing->value = $value;
                    $existing->updated_at = now();
                    $existing->save();
                } else {
                    Setting::insert(array_merge($data, ['value' => $value, 'created_at' => now(), 'updated_at' => now()]));
                }
        }
        // Settings Cache forget
        AdminSettingCacheForget();
        comapnySettingCacheForget();
        
        session()->put('success_message', __('Currency Setting save successfully.'));
        return redirect()->back();
    }

    public function storageStore(Request $request)
    {
        if (Auth::user()->isAbleTo('setting storage manage')) {
            $post = $request->all();
            unset($post['_token']);

            if ($request->storage_setting == 'wasabi') {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'wasabi_key' => 'required',
                        'wasabi_secret' => 'required',
                        'wasabi_region' => 'required',
                        'wasabi_bucket' => 'required',
                        'wasabi_url' => 'required',
                        'wasabi_root' => 'required',
                        'wasabi_max_upload_size' => 'required',
                        'wasabi_storage_validation' => 'required',
                    ]
                );
            } elseif ($request->storage_setting == 's3') {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        's3_key' => 'required',
                        's3_secret' => 'required',
                        's3_region' => 'required',
                        's3_bucket' => 'required',
                        's3_url' => 'required',
                        's3_endpoint' => 'required',
                        's3_max_upload_size' => 'required',
                        's3_storage_validation' => 'required',
                    ]
                );
            } else {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'local_storage_max_upload_size' => 'required',
                        'local_storage_validation' => 'required',
                    ]
                );
            }

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $post['s3_storage_validation'] = isset($request->s3_storage_validation) ? implode(",", $request->s3_storage_validation) : null;
            $post['wasabi_storage_validation'] = isset($request->wasabi_storage_validation) ? implode(",", $request->wasabi_storage_validation) : null;
            $post['local_storage_validation'] = isset($request->local_storage_validation) ? implode(",", $request->local_storage_validation) : null;

            // For super admin, always use business = 0
            $businessId = 0;
            
            foreach ($post as $key => $value) {
                // Define the data to be updated or inserted
                $data = [
                    'key' => $key,
                    'business' => $businessId,
                    'created_by' => creatorId(),
                ];

                // Check if the record exists, and update or insert accordingly
                $existing = Setting::where(['key' => $key, 'business' => $businessId])->first();
                if ($existing) {
                    $existing->value = $value;
                    $existing->updated_at = now();
                    $existing->save();
                } else {
                    Setting::insert(array_merge($data, ['value' => $value, 'created_at' => now(), 'updated_at' => now()]));
                }
            }
            // Settings Cache forget
            AdminSettingCacheForget();
            comapnySettingCacheForget();
            
            session()->put('success_message', __('Storage Setting save sucessfully.'));
            return redirect()->back();
        } else {
            session()->put('error_message', __('Permission denied.'));
            return redirect()->back();
        }
    }

    public function seoSetting(Request $request)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'meta_title' => 'required|string',
                'meta_keywords' => 'required|string',
                'meta_description' => 'required|string',
                'meta_image' => 'mimes:jpeg,jpg,png,gif',
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        if ($request->hasFile('meta_image')) {
            $filenameWithExt = $request->file('meta_image')->getClientOriginalName();
            $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension       = $request->file('meta_image')->getClientOriginalExtension();
            $fileNameToStore = $filename . '_' . time() . '.' . $extension;

            $uplaod = upload_file($request, 'meta_image', $fileNameToStore, 'meta');

            if ($uplaod['flag'] == 1) {
                // old img delete
                $settings = getAdminAllSetting();
                if ((!empty($settings['meta_image'])) && strpos($settings['meta_image'], 'meta_image.png') == false && check_file($settings['meta_image'])) {
                    delete_file($settings['meta_image']);
                }
            } else {
                return redirect()->back()->with('error', $uplaod['msg']);
            }
        }

        try {
            $post = $request->all();
            unset($post['_token'], $post['_method']);
            if ((isset($uplaod)) && ($uplaod['flag'] == 1) && (!empty($uplaod['url']))) {
                $post['meta_image'] =$uplaod['url'];
            }

            // For super admin, always use business = 0
            $businessId = 0;
            
            foreach ($post as $key => $value) {
                // Define the data to be updated or inserted
                $data = [
                    'key' => $key,
                    'business' => $businessId,
                    'created_by' => creatorId(),
                ];

                // Check if the record exists, and update or insert accordingly
                $existing = Setting::where(['key' => $key, 'business' => $businessId])->first();
                if ($existing) {
                    $existing->value = $value;
                    $existing->updated_at = now();
                    $existing->save();
                } else {
                    Setting::insert(array_merge($data, ['value' => $value, 'created_at' => now(), 'updated_at' => now()]));
                }
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
        // Settings Cache forget
        AdminSettingCacheForget();
        comapnySettingCacheForget();
        
        session()->put('success_message', __('SEO setting successfully updated.'));
        return redirect()->back();
    }

    public function customJsStore(Request $request)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'custom_js' => 'required',
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        $customJs = $request->custom_js;

        // Remove existing <script> tags
        $customJs = preg_replace('/<script.*?>.*?<\/script>/si', '', $customJs);

        $customJs = htmlspecialchars($customJs);

        // For super admin, always use business = 0
        // Define the data to be updated or inserted
        $data = [
            'key' => 'custom_js',
            'business' => 0,
            'created_by' => creatorId(),
        ];

        // Check if the record exists, and update or insert accordingly
        Setting::updateOrInsert($data, ['value' => $customJs]);


        // Settings Cache forget
        AdminSettingCacheForget();
        comapnySettingCacheForget();
        
        session()->put('success_message', __('Custom JS save sucessfully.'));
        return redirect()->back();
    }

    public function customCssStore(Request $request)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'custom_css' => 'required',
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        $customCss = $request->custom_css;

        // Remove existing <style> tags
        $customCss = preg_replace('/<style.*?>.*?<\/style>/si', '', $customCss);

        $customCss = htmlspecialchars($customCss);

        // For super admin, always use business = 0
        // Define the data to be updated or inserted
        $data = [
            'key' => 'custom_css',
            'business' => 0,
            'created_by' => creatorId(),
        ];

        // Check if the record exists, and update or insert accordingly
        Setting::updateOrInsert($data, ['value' => $customCss]);


        // Settings Cache forget
        AdminSettingCacheForget();
        comapnySettingCacheForget();
        
        session()->put('success_message', __('Custom CSS save sucessfully.'));
        return redirect()->back();
    }

    public function CookieSetting(Request $request)
    {
        if ($request->has('enable_cookie')) {
            $validator = \Validator::make($request->all(), [
                'cookie_title' => 'required',
                'cookie_description' => 'required',
                'strictly_cookie_title' => 'required',
                'strictly_cookie_description' => 'required',
                'more_information_description' => 'required',
                'contactus_url' => 'required',
            ]);
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
        }


        if ($request->has('enable_cookie')) {
            $post = $request->all();
            unset($post['_token'], $post['_method']);

            $post['cookie_logging'] = isset($request->cookie_logging) ? $request->cookie_logging : 'off';
            
            // For super admin, always use business = 0
            $businessId = 0;
            
            foreach ($post as $key => $value) {
                // Define the data to be updated or inserted
                $data = [
                    'key' => $key,
                    'business' => $businessId,
                    'created_by' => creatorId(),
                ];

                // Check if the record exists, and update or insert accordingly
                $existing = Setting::where(['key' => $key, 'business' => $businessId])->first();
                if ($existing) {
                    $existing->value = $value;
                    $existing->updated_at = now();
                    $existing->save();
                } else {
                    Setting::insert(array_merge($data, ['value' => $value, 'created_at' => now(), 'updated_at' => now()]));
                }
            }
        } else {
            // For super admin, always use business = 0
            // Define the data to be updated or inserted
            $data = [
                'key' => 'enable_cookie',
                'business' => 0,
                'created_by' => creatorId(),
            ];

            // Check if the record exists, and update or insert accordingly
            Setting::updateOrInsert($data, ['value' => 'off']);
        }
        // Settings Cache forget
        AdminSettingCacheForget();
        comapnySettingCacheForget();
        
        session()->put('success_message', __('Setting save sucessfully.'));
        return redirect()->back();
    }

    public function CookieConsent(Request $request)
    {
        if (admin_setting('enable_cookie') == "on" &&  admin_setting('cookie_logging') == "on") {
            try {

                $whichbrowser = new \WhichBrowser\Parser($_SERVER['HTTP_USER_AGENT']);
                // Generate new CSV line
                $browser_name = $whichbrowser->browser->name ?? null;
                $os_name = $whichbrowser->os->name ?? null;
                $browser_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? mb_substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : null;
                $device_type = GetDeviceType($_SERVER['HTTP_USER_AGENT']);

                $ip = $_SERVER['REMOTE_ADDR'];
                $query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));

                if ($query['status'] == 'success') {
                    $date = (new \DateTime())->format('Y-m-d');
                    $time = (new \DateTime())->format('H:i:s') . ' UTC';


                    $new_line = implode(',', [$ip, $date, $time, implode('-', $request['cookie']), $device_type, $browser_language, $browser_name, $os_name, isset($query) ? $query['country'] : '', isset($query) ? $query['region'] : '', isset($query) ? $query['regionName'] : '', isset($query) ? $query['city'] : '', isset($query) ? $query['zip'] : '', isset($query) ? $query['lat'] : '', isset($query) ? $query['lon'] : '']);
                    if (!check_file('/uploads/sample/cookie_data.csv')) {
                        $first_line = 'IP,Date,Time,Accepted-cookies,Device type,Browser anguage,Browser name,OS Name,Country,Region,RegionName,City,Zipcode,Lat,Lon';
                        file_put_contents(base_path() . '/uploads/sample/cookie_data.csv', $first_line . PHP_EOL, FILE_APPEND | LOCK_EX);
                    }
                    file_put_contents(base_path() . '/uploads/sample/cookie_data.csv', $new_line . PHP_EOL, FILE_APPEND | LOCK_EX);
                }
            } catch (\Throwable $th) {
                return response()->json('error');
            }
            return response()->json('success');
        }
        return response()->json('error');
    }
    public function updateNoteValue(Request $request)
    {
        $symbol_position = 'pre';
        $symbol = '$';
        $format = '1';
        $price  = '10000';
        $number = explode('.', $price);
        $length = strlen(trim($number[0]));
        $currency_symbol = explode('-',$request->defult_currancy);
        if ($length > 3) {
            $decimal_separator  = isset($request->float_number) && $request->float_number == 'dot' ? '.' : ',';
            $thousand_separator = isset($request->thousand_separator) && $request->thousand_separator == 'dot' ? '.' : ',';
        } else {
            $decimal_separator  = isset($request->decimal_separator) && $request->decimal_separator === 'dot'  ? '.' : ',';
            $thousand_separator = isset($request->thousand_separator) && $request->thousand_separator === 'dot' ? '.' : ',';
        }

        if (isset($request->site_currency_symbol_position) && $request->site_currency_symbol_position == "post") {
            $symbol_position = 'post';
        }

        if (isset($request->defult_currancy)) {
            $symbol = $request->defult_currancy;
        }

        if (isset($request->currency_format)) {
            $format = $request->currency_format;
        }
        if (isset($request->currency_space)) {
            $currency_space = isset($request->currency_space) ? $request->currency_space : '';
        }
        if (isset($request->site_currency_symbol_name)) {
            $symbol = $request->site_currency_symbol_name == 'symbol' ? $currency_symbol[0] : $currency_symbol[1];
        }
        $formatted_price = (
            ($symbol_position == "pre")  ?  $symbol : '') . (isset($currency_space) && $currency_space == 'withspace' ? ' ' : '')
            . number_format($price, $format, $decimal_separator, $thousand_separator) . (isset($currency_space) && $currency_space == 'withspace' ? ' ' : '') .
            (($symbol_position == "post") ?  $symbol : '');
        return response()->json(['success' => true,'formatted_price' => $formatted_price]);
    }
}
