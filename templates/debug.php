<h2>Debug</h2>

<div id="test-response" class="jbr-bu-output"></div>

<button class="jbr-bu-button" data-cmd="test" data-show-response="#test-response">test encryption</button>

<button class="jbr-bu-button" data-cmd="findBinaries" data-show-response="#test-response">find binaries</button>

<button class="jbr-bu-button" data-cmd="testPwd" data-show-response="#test-response">pwd</button>


<h2>Send an Email</h2>

<div id="email-response" class="jbr-bu-flash-response"></div>

<table class="jbr-bu-foo">
    <tr>
        <td>
            <form class="jbr-bu-form" data-flash-response="#email-response" action="<?= admin_url('admin-ajax.php') ?>">

                <input type="hidden" name="cmd" value="sendEmail">
                <table class="form-table">
                    <tr>
                        <th><label for="emailTo">To</label></th>
                        <td><input id="emailTo" name="emailTo" type="text" ></td>
                    </tr>
                    <tr>
                        <th><label for="emailSubject">Subject</label></th>
                        <td><input id="emailSubject" name="emailSubject" type="text" ></td>
                    </tr>
                    <tr>
                        <th><label for="emailBody">Body</label></th>
                        <td><textarea id="emailBody" name="emailBody"></textarea></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><button type="submit">send</button></td>
                    </tr>
                </table>
            </form>
        </td>
    
    <td>
        <p>Send a test email to see if we can...</p>
        </td>
    </tr>
</table>


<h2>Environment</h2>
<?php 
$s = $this->getSettings();
$ftp = $s['ftpInfo'];
$d = $this->getData();
ksort($d);
unset($s['ftpInfo']);
ksort($s);
ksort($ftp);
$ftp['password'] = preg_replace('!.!', '*', $ftp['password']);
?>
<h3>General Settings</h3>
<table class="jbr-bu-dump">
    <?php foreach ($s as $k => $v): ?>
    <tr>
        <th><?= $k ?></th>
        <td><?= $v ?></td>
    </tr>
    <?php endforeach ?>
</table>

<h3>FTP Server</h3>
<table class="jbr-bu-dump">
    <?php foreach ($ftp as $k => $v): ?>
    <tr>
        <th><?= $k ?></th>
        <td><?= $v ?></td>
    </tr>
    <?php endforeach ?>
</table>

<h3>Data</h3>
<table class="jbr-bu-dump">
    <?php foreach ($d as $k => $v): ?>
    <tr>
        <th><?= $k ?></th>
        <td><?= $v ?> (<?= $this->stampToDate($v) ?>)</td>
    </tr>
    <?php endforeach ?>
</table>

<h3>Time</h3>
<table class="jbr-bu-dump">
    <tr>
        <th>Time Zone</th>
        <td><?= date_default_timezone_get() ?></td>
    </tr>
    <tr>
        <th>Local Time</th>
        <td><?= $this->stampToDate( time() ) ?></td>
    </tr>
    <tr>
        <th>UTC Time</th>
        <td><?= gmstrftime('%Y-%m-%d %H:%M:%S') ?></td>
    </tr>
</table>