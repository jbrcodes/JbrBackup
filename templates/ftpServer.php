<?php $i = $this->getSettings()['ftpInfo'] ?>

<div id="ftp-response" class="jbr-bu-flash-response"></div>

<h2>Remote FTP Server Info</h2>

<table class="jbr-bu-foo">
    <tr>
        <td>
            <form class="jbr-bu-form" data-flash-response="#ftp-response" action="<?= admin_url('admin-ajax.php') ?>">

                <input type="hidden" name="cmd" value="saveFtpInfo">
                <table class="form-table">
                    <tr>
                        <th><label for="host">Host</label></th>
                        <td><input id="host" name="host" type="text" value="<?= $i['host'] ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="user">User</label></th>
                        <td><input id="user" name="user" type="text" value="<?= $i['user'] ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="password">Password</label></th>
                        <td><input id="password" name="password" type="password" value="<?= $i['password'] ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="passiveMode">Use Passive Mode</label></th>
                        <td><?= $this->renderCheckbox('passiveMode', 'y', $i['passiveMode'] == 'y') ?></td>
                    </tr>
                    <tr>
                        <th><label for="remoteDbDir">Remote DB Directory</label></th>
                        <td><input id="remoteDbDir" name="remoteDbDir" type="text" placeholder="/lads/db" value="<?= $i['remoteDbDir'] ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="remoteFilesDir">Remote File Directory</label></th>
                        <td><input id="remoteFilesDir" name="remoteFilesDir" type="text" placeholder="/lads/uploads" value="<?= $i['remoteFilesDir'] ?>"></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><button type="submit">Save</button></td>
                    </tr>
                </table>
            </form>
        </td>
        <td>
            <p>Here we can include a bunch of helpful info!
                At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti.</p><p>Tquos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio.
            </p>
            <dl>
                <dt>Use Passive Mode</dt>
                <dd>Depending on remote server configuration, a connection may not work without this. Try to connect first without, and if you are sure that the other info is correct, try with this option checked.</dd>
                <dt>Remote DB Directory</dt>
                <dd>This is where the DB dumps will be stored. We figure you want to store them apart from the files that are backed up, no?</dd>
                <dt>Remote File Directory</dt>
                <dd>Files from the WordPress <code>uploads</code> directory will be saved. Maybe it makes sense to also name the remote directory the same.</dd>
            </dl>
        </td>
    </tr>
</table>

<h2>Test Server Connection</h2>

<table class="jbr-bu-foo">
    <tr>
        <td>

            <button class="jbr-bu-button" data-cmd="testFtpConn" data-flash-response="#ftp-response">test connection</button>
        </td>
        <td>
        
                    <p>Press the button to test the FTP credentials. This can take a few seconds if the user or password is wrong, since the system will pause three seconds.</p>
        
        </td>
    </tr>
</table>
