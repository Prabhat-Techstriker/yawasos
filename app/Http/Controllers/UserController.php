<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Carbon\Carbon;

class UserController extends Controller
{
    public function update(Request $request) {
    	$validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric', 
            'longitude' => 'required|numeric',
            'device_token' => 'required|string',
            'image_path' => 'string', 
            'message' => 'string' 
        ]);

		if ($validator->fails()) {
			return response()->json(['success' => false, 'error' => $validator->errors(), 'message' => 'Validation failed'], 401);
		}

		try {
            $user = Auth::user();
            $user->latitude = is_null($request->latitude) ? $user->latitude : $request->latitude;
            $user->longitude = is_null($request->longitude) ? $user->longitude : $request->longitude;
	        $user->device_token = is_null($request->device_token) ? $user->device_token : $request->device_token;
	        $user->message = is_null($request->message) ? $user->message : $request->message;
	        $user->save();

            return response()->json(['success' => true, 'user' =>  $user, 'message' => 'User updated.'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, "message" => "User not found."], 404);
        }    
    }

    public function delete($id) {
    	try {
            $user = User::findOrFail($id);
            $user->delete();
            return response()->json(['success' => true, 'message' => 'User has been deleted.'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, "message" => "User not found"], 404);
        }
    }

    public function show() {
        return response()->json(['success' => true, 'users' =>  User::all(), 'message' => 'Fetched all users.'], 200);
    }

    public function uploadAndNotify(Request $request) {
        $fileNameToStore = null;

        $validator = Validator::make($request->all(), [
            'message' => 'string|nullable',
            'image'   => 'image|nullable|image:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);            
        }

        try {
            $user = Auth::user();
            $allTokens = User::where('id', '!=' , $user->id)->pluck('device_token')->toArray();
            // dd($allTokens);
            if ($request->file('image')) {
                $filenameWithExt = $request->file('image')->getClientOriginalName();
                $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);            
                $extension = $request->file('image')->getClientOriginalExtension();
                $mimeType = $request->file('image')->getClientMimeType();

                $fileNameToStore = str_replace(" ", "-", $filename).'_'.time().'.'.$extension;

                $path = $request->file('image')->storeAs('images', $fileNameToStore);
            }

            $user->message = $request->message;
            $user->image_path = $fileNameToStore;
            $user->save();

            
            if(count($allTokens) > 0) {
                $title = "Notification from Yawa SOS";
                $notifyAll = sendMultiple($allTokens, $title, $user->message, $user->image_path, $user->latitude, $user->longitude, $user->updated_at); //using helper.php to send fcm notiifcations
                return response()->json([ 'success' => true, 'message' => 'Notification has been sent to all nearby users!!'], 201);
            }
            return response()->json([ 'success' => true, 'user' => $user, 'message' => 'Data has been saved successfully'], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, "message" => "User not found."], 404);
        }
    }

    public function allNotifications(Request $request) {
        try {
            $sender = Auth::user();
            $notificationDate = $request->notification_date;

            $allNotifications = User::select('id', 'latitude', 'longitude', 'image_path', 'message', 'updated_at AS notification_date ')
                                ->where('id', '!=' , $sender->id)
                                ->whereDate('updated_at', $notificationDate)
                                ->where(function($q) {
                                    $q->where('image_path', '!=', null)
                                    ->orWhere('message', '!=', null);
                                })
                                ->get();

            if(count($allNotifications) == '0') {
                return response()->json(["message" => "No notification found"], 201);
            }
            return response()->json(['success' => true, 'notifications' => $allNotifications, "message" => "Fetched all notifications of: ".$notificationDate."."], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, "message" => "User not found"], 404);
        }
    }

    public function usersNotifications(Request $request) {
        try {
            $sender = Auth::user();
            $notificationDate = $request->notification_date;

            $allNotifications = User::select('id', 'name', 'latitude', 'longitude', 'image_path', 'message', 'updated_at AS notification_date')
                                ->where('updated_at', ">",Carbon::now()->subDay())->where("updated_at","<",Carbon::now()) // notifications of last 24 hours
                                ->where(function($q) {
                                    $q->where('image_path', '!=', null)
                                    ->orWhere('message', '!=', null);
                                })->get();
                             
            $allNotificationsArray = json_decode($allNotifications, true);
            $notifyIds = array_column($allNotificationsArray, 'id');
            
            $allUsers = User::select('id', 'name', 'latitude', 'longitude')
                        ->where('id', '!=' , $sender->id)
                        ->whereNotIn('id', $notifyIds)->get();

            $me = User::select('id', 'name', 'latitude', 'longitude')->where('id', $sender->id)->first();

            $allData = ['users'=> $allUsers, 'notifications' => $allNotifications, 'me' => $me];
            return response()->json(['status' => 'success', 'allData' => $allData, "message" => "Fetched all data."], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'failed', "message" => "User not found"], 404);
        }
    }
}
