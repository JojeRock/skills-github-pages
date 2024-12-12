<?php
error_reporting(0);
$telegram_ip_ranges = [
	['lower' => '149.154.160.0', 'upper' => '149.154.175.255'],
	['lower' => '91.108.4.0', 'upper' => '91.108.7.255']
];
$ip_dec = (float) sprintf('%u', ip2long($_SERVER['REMOTE_ADDR']));
$ok = false;
foreach ($telegram_ip_ranges as $telegram_ip_range) {
	if (!$ok) {
		$lower_dec = (float) sprintf('%u', ip2long($telegram_ip_range['lower']));
		$upper_dec = (float) sprintf('%u', ip2long($telegram_ip_range['upper']));
		$ok = ($ip_dec >= $lower_dec && $ip_dec <= $upper_dec) ? true : false;
	}
}
if (!$ok) exit();
##----------------------
define('API_KEY', '7491373246:AAEB5GCCyhdkXksAwk0A9heElp4KdIJ4-LA');
define('API_KEY_LOCK', '7491373246:AAEB5GCCyhdkXksAwk0A9heElp4KdIJ4-LA');
$admin = '5107034738';
$bot_username = 'QRCodeScanerr_bot';
$channel = 'Purgatoryofmusic';
##----------------------
define('Encode_1', 'https://api.qrserver.com/v1/create-qr-code/?size=500x500&charset-source=utf-8&data=');
define('Encode_2', 'https://chart.googleapis.com/chart?chs=500x500&cht=qr&choe=UTF-8&chl=');
define('Decode', 'http://api.qrserver.com/v1/read-qr-code/?fileurl=');
##----------------------
$update = json_decode(file_get_contents('php://input'));
$chat_id = isset($update->message->chat->id) ? $update->message->chat->id : null;
$user_id = isset($update->message->from->id) ? $update->message->from->id : null;
$first_name = isset($update->message->from->first_name) ? $update->message->from->first_name : null;
$last_name = isset($update->message->from->last_name) ? $update->message->from->last_name : null;
$full_name = isset($last_name) ? "$first_name $last_name" : $first_name;
$username = isset($update->message->from->username) ? $update->message->from->username : null;
$msg_id = isset($update->message->message_id) ? $update->message->message_id : null;
$msg_text = isset($update->message->text) ? $update->message->text : null;
$caption = isset($update->message->caption) ? $update->message->caption : null;
$query_from_id = isset($update->inline_query->from->id) ? $update->inline_query->from->id : null;
$query_id = isset($update->inline_query->id) ? $update->inline_query->id : null;
$query_text = isset($update->inline_query->query) ? $update->inline_query->query : null;
##----------------------
if ($update->message->photo) {
	$photo = $update->message->photo;
	$count = count($photo)-1;
	$file_id = $photo[$count]->file_id;
	$type = 'photo';
}
elseif ($update->message->audio) {
	$file_id = $update->message->audio->file_id;
	$type = 'audio';
}
elseif ($update->message->document) {
	$file_id = $update->message->document->file_id;
	$type = 'document';
}
elseif ($update->message->video) {
	$file_id = $update->message->video->file_id;
	$type = 'video';
}
elseif ($update->message->voice) {
	$file_id = $update->message->voice->file_id;
	$type = 'voice';
}
elseif ($update->message->sticker) {
	$file_id = $update->message->sticker->file_id;
	$type = 'sticker';
}
elseif ($update->message->contact) {
	$phone_number = $update->message->contact->phone_number;
	$first_name = $update->message->contact->first_name;
	$last_name = $update->message->contact->last_name;
	$type = 'contact';
}
elseif ($update->message->venue) {
	$title = $update->message->venue->title;
	$address = $update->message->venue->address;
	$latitude = $update->message->venue->location->latitude;
	$longitude = $update->message->venue->location->longitude;
	$type = 'venue';
}
elseif ($update->message->location) {
	$latitude = $update->message->location->latitude;
	$longitude = $update->message->location->longitude;
	$type = 'location';
}
##----------------------Functions
function bot($method, $data = [])
{
	$ch = curl_init('https://api.telegram.org/bot' . API_KEY . '/' . $method);
	curl_setopt_array($ch,
	[
		CURLOPT_POST => true,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POSTFIELDS => $data
	]);
	$result = curl_exec($ch);
	curl_close($ch);
	return (!empty($result) ? json_decode($result) : false);
}
function sendAction($chat_id, $action = 'typing')
{
	bot('sendChataction',
	[
		'chat_id' => $chat_id,
		'action' => $action
	]);
}
function sendMessage($chat_id, $text, $message_id = '')
{
	return bot('sendMessage',
	[
		'chat_id' => $chat_id,
		'text' => $text,
		'parse_mode' => 'html',
		'reply_to_message_id' => $message_id
	]);
}
function forwardMessage($chat_id, $from_chat_id, $message_id)
{
	return bot('ForwardMessage',
	[
		'chat_id' => $chat_id,
		'from_chat_id' => $from_chat_id,
		'message_id' => $message_id
	]);
}
function getChat($chatid)
{
	return bot('getChat',
	[
		'chat_id' => $chatid
	]);
}
function inChat($user_id, $ch, $lockChannel)
{
	if ($lockChannel == 'yes')
	{
		$data = json_decode(file_get_contents('https://api.telegram.org/bot' . API_KEY_LOCK . '/getChatMember?chat_id=@' . $ch . '&user_id=' . $user_id))->result->status;
		if ($data == 'creator' || $data == 'administrator' || $data == 'member') return true;
		else return false;
	}
	else return true;
}
function array_trim_end($array)
{
	$num = count($array)-1;
	unset($array[$num]);
	return $array;
}
##----------------------
$members = @file_get_contents('members.txt');
$membersRecent = @file_get_contents('membersRecent.txt');
$step = @file_get_contents('step.txt');
$lockChannel = is_file('lockChannel.txt') ? file_get_contents('lockChannel.txt') : true;
##----------------------
if (isset($user_id) && strpos($members, "{$user_id},") === false && $user_id != $admin) {
	file_put_contents('members.txt', "{$members}{$user_id},");
}
if (isset($user_id) && $user_id != $admin){
	$membersRecent = str_replace("{$user_id},", '', $membersRecent);
	file_put_contents('membersRecent.txt', "{$user_id},{$membersRecent}");
}
##----------------------
if (isset($query_id)) {
	if (inChat($query_from_id, $channel, $lockChannel) == false) {
		bot('answerInlineQuery',
		[
			'inline_query_id' => $query_id,
			'cache_time' => 1,
			'is_personal' => false,
			'results' => json_encode(
				[
					[
						'type' => 'article',
						'id' => base64_encode(1),
						'title' => '❌ خطا',
						'description' => "❌ شما عضو کانال $channel@ نیستید.",
						'input_message_content' => ['message_text' => "❌ متاسفانه شما عضو کانال  @$channel نیستید.\n\n💯برای استفاده از ربات ملزم به عضویت در آن هستید.\n🔅لطفا ابتدا در [کانال](https://telegram.me/$channel) عضو شوید، سپس مجدادا امتحان کنید.", 'parse_mode' => 'markdown', 'disable_web_page_preview' => true],
						'reply_markup' =>
						[
							'inline_keyboard' =>
							[
								[['text' => "📣 @{$channel}", 'url' => "https://telegram.me/{$channel}"]],
								[['text' => '↩️ حالت درون خطی', 'switch_inline_query' => '']]
							]
						]
					]
				]
			)
		]);
	}
	else {
		if ($query_text == null) {
			bot('answerInlineQuery',
			[
				'inline_query_id' => $query_id,
				'cache_time' => 1,
				'is_personal' => false,
				'switch_pm_text' => 'لطفا چیزی بنویسید',
				'switch_pm_parameter' => 'start'
			]);
		}
		else {
			bot('answerInlineQuery',
			[
				'inline_query_id' => $query_id,
				'cache_time' => 1,
				'results' => json_encode(
					[
						[
							'type' => 'photo',
							'id' => base64_encode(1),
							'photo_url' => Encode_2 . urlencode($query_text),
							'title' => 'Qr Code',
							'caption' => "🤖 @{$bot_username}",
							'description' => $query_text,
							'thumb_url' => Encode_2 . urlencode($query_text),
							'reply_markup' =>
							[
								'inline_keyboard' =>
								[
									[['text' => '↩️ حالت درون خطی', 'switch_inline_query' => '']]
								]
							]
						]
					]
				)
			]);
		}
	}
}
##----------------------
if (inChat($user_id, $channel, $lockChannel) == false) {
	sendAction($chat_id);
	bot('sendMessage',
	[
		'chat_id' => $chat_id,
		'text' => "🚨 برای استفاده از ربات می بایست عضو کانال @" . $channel . " باشید.\n\n🔰 لطفا ابتدا در کانال @" . $channel . " عضو شوید.\n\n👇🏻 پس از عضو شدن دستور /start را ارسال نمایید.",
		'reply_to_message_id' => $msg_id,
		'parse_mode' => 'markdown',
		'disable_web_page_preview' => true,
		'reply_markup' => json_encode(
			[
				'inline_keyboard' =>
				[
					[['text' => "📣 @{$channel}", 'url' => "https://telegram.me/{$channel}"]]
				]
			]
		)
	]);
	exit();
}
##----------------------
if ((strtolower($msg_text) == '/panel' or strtolower($msg_text) == '/manage' or $msg_text == '🔙 بازگشت') and $user_id == $admin) {
	sendAction($chat_id);
	file_put_contents('step.txt', 'none');
	bot('sendMessage',
	[
		'chat_id' => $chat_id,
		'text' => '🧮 به بخش مدیریت ربات خوش آمدید.',
		'reply_markup' => json_encode(
			[
				'keyboard' =>
				[
					[['text' => '📊 آمار'], ['text' => '🔐 قفل کانال']],
					[['text' => '🚀 هدایت همگانی'], ['text' => '🗣 پیام همگانی']],
					[['text' => '🔙 خروج']]
				],
				'resize_keyboard' => true
			]
		)
	]);
}
elseif ($msg_text == '📊 آمار' && $user_id == $admin) {
	sendAction($chat_id);
	$members = array_reverse(array_trim_end(explode(',', $members)));
	$membersCount = count($members);
	$membersRecent = array_trim_end(explode(',', $membersRecent));
	$i = 1;
	$members_text = '';
	foreach($members as $id) {
		if ($i <= 16) {
			$members_text .= "{$i} - <a href='tg://user?id=$id'>" . str_replace(['<', '>'], '', getChat($id)->result->first_name) . "</a>\n";
			$i++;
		}
	}
	$i = 1;
	$recentMembers_text = '';
	foreach($membersRecent as $id) {
		if ($i <= 16) {
			$recentMembers_text .= "{$i} - <a href='tg://user?id=$id'>" . str_replace(['<', '>'], '', getChat($id)->result->first_name) . "</a>\n";
			$i++;
		}
	}
	sendMessage($chat_id, "📊 تعداد کاربران : <b>{$membersCount}</b>\n\n🎁 کاربران جدید :\n{$members_text}\n🔰 کاربران اخیر :\n{$recentMembers_text}", $message_id);
}
elseif ($msg_text == '🗣 پیام همگانی' && $user_id == $admin) {
	sendAction($chat_id);
	file_put_contents('step.txt', 's2a');
	bot('sendMessage',
	[
		'chat_id' => $chat_id,
		'text'=>'✉️ لطفا پیام خود را برای ارسال همگانی بفرستید.',
		'reply_markup' => json_encode(
			[
				'keyboard' =>
				[
					[['text' => '🔙 بازگشت']]
				],
				'resize_keyboard' => true
			]
		)
	]);
}
elseif ($step == 's2a' && $user_id == $admin) {
	sendAction($chat_id);
	file_put_contents('step.txt', 'none');
	sendMessage($chat_id, '✉️ ربات در حال ارسال پیام شما برای تمامی کاربران است ...', $msg_id);
	$members = array_trim_end(explode(',', $members));
	$membersCount = count($members);
	foreach($members as $id) {
		sendMessage($id, $msg_text);
	}
	sendAction($chat_id);
	bot('sendMessage', [
		'chat_id' => $chat_id,
		'text' => "✅ پیام شما برای $membersCount کاربر ارسال شد.",
		'reply_to_message_id' => $msg_id,
		'reply_markup' => json_encode(
			[
				'keyboard' =>
				[
					[['text' => '📊 آمار'], ['text' => '🔐 قفل کانال']],
					[['text' => '🚀 هدایت همگانی'], ['text' => '🗣 پیام همگانی']],
					[['text' => '🔙 خروج']]
				],
				'resize_keyboard'=>true
			]
		)
	]);
}
elseif ($msg_text == '🚀 هدایت همگانی' && $user_id == $admin) {
	sendAction($chat_id);
	file_put_contents('step.txt', 'f2a');
	bot('sendMessage',
	[
		'chat_id'=>$chat_id,
		'text'=>'🚀 لطفا پیام خود را برای هدایت همگانی بقرستید.',
		'reply_markup' => json_encode(
			[
				'keyboard' =>
				[
					[['text'=>'🔙 بازگشت']]
				],
				'resize_keyboard' => true
			]
		)
	]);
}
elseif ($step == 'f2a' && $user_id == $admin) {
	sendAction($chat_id);
	file_put_contents('step.txt', 'none');
	sendMessage($chat_id, '✉️ ربات در حال هدایت پیام شما برای تمامی کاربران است ...', $msg_id);
	$members = array_trim_end(explode(',', $members));
	$membersCount = count($members);
	foreach($members as $id) {
		forwardMessage($id, $chat_id, $msg_id);
	}
	sendAction($chat_id);
	bot('sendMessage', [
		'chat_id' => $chat_id,
		'text' => "✅ پیام شما برای $membersCount کاربر هدایت گردید.",
		'reply_to_message_id' => $msg_id,
		'reply_markup' => json_encode(
			[
				'keyboard' =>
				[
					[['text' => '📊 آمار'], ['text' => '🔐 قفل کانال']],
					[['text' => '🚀 هدایت همگانی'], ['text' => '🗣 پیام همگانی']],
					[['text' => '🔙 خروج']]
				],
				'resize_keyboard'=>true
			]
		)
	]);
}
elseif ($msg_text == '🔐 قفل کانال' && $user_id == $admin) {
	sendAction($chat_id);
	file_put_contents('step.txt', 'lock');
	if ($lockChannel == 'yes') {
		$text = "📣 قفل کانال فعال است 🔒\n\n🤔 آیا می خواهید آنرا غیر فعال کنید؟";
		$keyboard = '🔓 باز کردن';
	}
	else {
		$text = "📣 قفل کانال غیر فعال است 🔓\n\n🤔 آیا می خواهید آنرا فعال کنید؟";
		$keyboard = '🔒 قفل کردن';
	}
	bot('sendMessage',
	[
		'chat_id' => $chat_id,
		'text' => $text,
		'reply_markup' => json_encode(
			[
				'keyboard' =>
				[
					[['text' => $keyboard]],
					[['text' => '🔙 بازگشت']]
				],
				'resize_keyboard' => true
			]
		)
	]);
}
elseif ($step == 'lock' && $user_id == $admin) {
	sendAction($chat_id);
	file_put_contents('step.txt', 'none');
	if ($msg_text == '🔓 باز کردن'){
		$text = 'قفل کانال غیر فعال شد ✅';
		file_put_contents('lockChannel.txt', 'no');
	}
	elseif ($msg_text == '🔒 قفل کردن'){
		$text = 'قفل کانال فعال شد ✅';
		file_put_contents('lockChannel.txt', 'yes');
	}
	bot('sendMessage',
	[
		'chat_id' => $chat_id,
		'text' => $text,
		'reply_to_message_id' => $msg_id,
		'reply_markup' => json_encode(
			[
				'keyboard' =>
				[
					[['text' => '📊 آمار'], ['text' => '🔐 قفل کانال']],
					[['text' => '🚀 هدایت همگانی'], ['text' => '🗣 پیام همگانی']],
					[['text' => '🔙 خروج']]
				],
				'resize_keyboard'=>true
			]
		)
	]);
}
##----------------------
elseif (strtolower($msg_text) == '/start' or $msg_text == '🔙 خروج') {
	sendAction($chat_id);
	bot('sendMessage',
	[
		'chat_id' => $chat_id,
		'text' => "😁✋🏻 سلام $full_name\n🤖 به ربات ساختن و خواندن QrCode خوش آمدید.\n\n🎃 شما به وسیلهٔ این ربات می توانید QrCode بسازید و QrCode ها را بخوانید.\n\n📝 برای ساخت QrCode متن یا رسانهٔ مورد نظر خود را بفرستید.\n🔍 برای خواندن QrCode آنرا برای ربات بفرستید.\n\n📣 @$channel",
		'reply_markup' => json_encode(
			[
				'remove_keyboard' => true
			]
		)
	]);
}
elseif (strtolower($msg_text) == '/source') {
	sendAction($chat_id, 'upload_document');
	bot('sendDocument',
	[
		'chat_id' => $chat_id,
		'document' => 'https://github.com/oYSoF/Telegram-QrCode-Bot/archive/master.zip',
		'caption' => 'https://github.com/oYSoF/Telegram-QrCode-Bot/archive/master.zip'
	]);
}
##----------------------
elseif ($type == 'photo') {
	$file_path = bot('getfile', ['file_id' => $file_id])->result->file_path;
	$decode = json_decode(file_get_contents(Decode . 'https://api.telegram.org/file/bot' . API_KEY . '/' . $file_path))[0]->symbol[0]->data;
	$json_decode = json_decode($decode);
	$decode_type = $json_decode->type;
	$decode_file_id = $json_decode->file_id;
	$decode_caption = $json_decode->caption;
	if (isset($decode_type)) {
		if ($decode_type == 'photo') {
			sendAction($chat_id, 'upload_photo');
			bot('sendPhoto',
			[
				'chat_id' => $chat_id,
				'photo' => $decode_file_id,
				'caption' => $decode_caption,
				'reply_to_message_id' => $msg_id,
				'reply_markup' => json_encode(
					[
						'inline_keyboard' => [
							[['text' => '↩️ حالت درون خطی', 'switch_inline_query' => '']]
						]
					]
				)
			]);
		}
		elseif ($decode_type == 'audio') {
			sendAction($chat_id, 'upload_audio');
			bot('sendAudio',
			[
				'chat_id' => $chat_id,
				'audio' => $decode_file_id,
				'caption' => $decode_caption,
				'reply_to_message_id' => $msg_id,
				'reply_markup' => json_encode(
					[
						'inline_keyboard' => [
							[['text' => '↩️ حالت درون خطی', 'switch_inline_query' => '']]
						]
					]
				)
			]);
		}
		elseif ($decode_type == 'document') {
			sendAction($chat_id, 'upload_document');
			bot('sendDocument',
			[
				'chat_id' => $chat_id,
				'document' => $decode_file_id,
				'caption' => $decode_caption,
				'reply_to_message_id' => $msg_id,
				'reply_markup' => json_encode(
					[
						'inline_keyboard' => [
							[['text' => '↩️ حالت درون خطی', 'switch_inline_query' => '']]
						]
					]
				)
			]);
		}
		elseif ($decode_type == 'sticker') {
			bot('sendSticker',
			[
				'chat_id' => $chat_id,
				'sticker' => $decode_file_id,
				'reply_to_message_id' => $msg_id,
				'reply_markup' => json_encode(
					[
						'inline_keyboard' => [
							[['text' => '↩️ حالت درون خطی', 'switch_inline_query' => '']]
						]
					]
				)
			]);
		}
		elseif ($decode_type == 'video') {
			sendAction($chat_id, 'upload_video');
			bot('sendVideo',
			[
				'chat_id' => $chat_id,
				'video' => $decode_file_id,
				'caption' => $decode_caption,
				'reply_to_message_id' => $msg_id,
				'reply_markup' => json_encode(
					[
						'inline_keyboard' => [
							[['text' => '↩️ حالت درون خطی', 'switch_inline_query' => '']]
						]
					]
				)
			]);
		}
		elseif ($decode_type == 'voice') {
			sendAction($chat_id, 'record_audio');
			bot('sendVoice',
			[
				'chat_id' => $chat_id,
				'voice' => $decode_file_id,
				'caption' => $decode_caption,
				'reply_to_message_id' => $msg_id,
				'reply_markup' => json_encode(
					[
						'inline_keyboard' => [
							[['text' => '↩️ حالت درون خطی', 'switch_inline_query' => '']]
						]
					]
				)
			]);
		}
		elseif ($decode_type == 'contact') {
			$first = $json_decode->first_name;
			if ($first == '') $first = '‌';
			bot('sendContact',
			[
				'chat_id' => $chat_id,
				'phone_number' => $json_decode->phone_number,
				'first_name' => $first,
				'last_name' => $json_decode->last_name,
				'reply_to_message_id' => $msg_id,
				'reply_markup' => json_encode(
					[
						'inline_keyboard' => [
							[['text' => '↩️ حالت درون خطی', 'switch_inline_query' => '']]
						]
					]
				)
			]);
		}
		elseif ($decode_type == 'venue') {
			sendAction($chat_id, 'find_location');
			bot('sendVenue',
			[
				'chat_id' => $chat_id,
				'title' => $json_decode->title,
				'address' => $json_decode->address,
				'latitude' => $json_decode->latitude,
				'longitude' => $json_decode->longitude,
				'reply_to_message_id' => $msg_id,
				'reply_markup' => json_encode(
					[
						'inline_keyboard' => [
							[['text' => '↩️ حالت درون خطی', 'switch_inline_query' => '']]
						]
					]
				)
			]);
		}
		elseif ($decode_type == 'location') {
			sendAction($chat_id, 'find_location');
			bot('sendLocation',
			[
				'chat_id' => $chat_id,
				'latitude' => $json_decode->latitude,
				'longitude' => $json_decode->longitude,
				'reply_to_message_id' => $msg_id,
				'reply_markup' => json_encode(
					[
						'inline_keyboard' => [
							[['text' => '↩️ حالت درون خطی', 'switch_inline_query' => '']]
						]
					]
				)
			]);
		}
	}
	else {
		if ($decode != '') {
			sendAction($chat_id);
			bot('sendMessage',
			[
				'chat_id' => $chat_id,
				'text' => $decode,
				'reply_to_message_id' => $msg_id,
				'reply_markup' => json_encode(
					[
						'inline_keyboard' => [
							[['text' => '↩️ حالت درون خطی', 'switch_inline_query' => '']]
						]
					]
				)
			]);
		}
		else {
			sendAction($chat_id, 'upload_photo');
			bot('sendPhoto',
			[
				'chat_id' => $chat_id,
				'photo' => Encode_1 . urlencode(json_encode(['type' => $type, 'file_id' => $file_id, 'caption' => $caption])),
				'reply_to_message_id' => $msg_id,
				'reply_markup' => json_encode(
					[
						'inline_keyboard' => [
							[['text' => '↩️ حالت درون خطی', 'switch_inline_query' => '']]
						]
					]
				)
			]);
		}
	}
}
##----------------------
elseif (isset($msg_text)) {
	$characters = mb_strlen($msg_text, 'utf-8');
	if ($characters <= 1800) {
		sendAction($chat_id, 'upload_photo');
		bot('sendPhoto',
		[
			'chat_id' => $chat_id,
			'photo' => Encode_1 . urlencode($msg_text),
			'reply_to_message_id' => $msg_id,
			'reply_markup' => json_encode(
				[
					'inline_keyboard' => [
						[['text' => '↩️ حالت درون خطی', 'switch_inline_query' => '']]
					]
				]
			)
		]);
	}
	else {
		sendAction($chat_id);
		sendMessage($chat_id, "❌ متن طولانی قابل پذیرش نیست.\n\n♨️ تعداد کاراکتر مجاز : 1800\n🗒 تعداد کاراکتر متن شما : $characters", $msg_id);
	}
}
##----------------------
elseif (isset($type)) {
	sendAction($chat_id, 'upload_photo');
	if ($type == 'venue') $data = ['type' => $type, 'title' => $title, 'address' => $address, 'latitude' => $latitude, 'longitude' => $longitude];
	elseif ($type == 'location') $data = ['type' => $type, 'latitude' => $latitude, 'longitude' => $longitude];
	elseif ($type == 'contact') $data = ['type' => $type, 'phone_number' => $phone_number, 'first_name' => $first_name, 'last_name' => $last_name];
	else $data = ['type' => $type, 'file_id' => $file_id, 'caption' => $caption];
	bot('sendPhoto',
	[
		'chat_id' => $chat_id,
		'photo' => Encode_1 . urlencode(json_encode($data)),
		'reply_to_message_id' => $msg_id,
		'reply_markup' => json_encode(
			[
				'inline_keyboard' => [
					[['text' => '↩️ حالت درون خطی', 'switch_inline_query' => '']]
				]
			]
		)
	]);
}
