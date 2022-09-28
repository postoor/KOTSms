# 簡訊王 unofficial sdk

### Send SMS

```php
require __DIR__.'/vendor/autoload.php';

use postoor\KOTSms\Sms;

$sms = new Sms('username', 'password');

$options = ['Ftcode' => 'Sms'.rand(0, 10000)];
$messages = [];

$kmsGID = $sms->send('<phone number>', '我是中文', $options, $messages);

if ($kmsGID < 0) {
    var_dump($messages);
}

echo $kmsGID;
```

#### Request Data

| Key      | Desc.             | Max          | Note                   |
| -------- | ----------------- | ------------ | ---------------------- |
| username | Username          |              | required               |
| password | Password          |              | required               |
| dstaddr  | Phone Number      |              | required, phone_number |
| smbody   | SMS Message       | 160          | required, message      |
| dlvtime  | Delivery Time     |              | date(Y/m/d H:i:s)      |
| vldtime  | Validity Period   | 1800 ~ 28800 |                        |
| response | Callback Link     |              |                        |
| Ftcode   | Verification Code | 36           |                        |

### Get User Point

```php
use postoor\KOTSms\Sms;

$sms = new Sms('username', 'password');
$point = $sms->getPoint();

echo $point;

```

#### Request Data

| Key      | Desc.    | Max | Note     |
| -------- | -------- | --- | -------- |
| username | Username |     | required |
| password | Password |     | required |


### Get Sms Status

```php
use postoor\KOTSms\Sms;

$sms = new Sms('username', 'password');
$status = $sms->getSMSStatus(198987);

echo $status;

```

#### Request Data

| Key      | Desc.    | Max | Note     |
| -------- | -------- | --- | -------- |
| username | Username |     | required |
| password | Password |     | required |
| kmsgid   | kmsgid   |     | required |
