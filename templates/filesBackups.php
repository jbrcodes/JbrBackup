<?php $s = $this->getSettings() ?>
<?php $d = $this->getData() ?>

<div id="files-response" class="jbr-bu-flash-response"></div>

<h2>File Backup Frequency</h2>
<table class="jbr-bu-foo">
    <tr>
        <td>
         
            <form class="jbr-bu-form" data-flash-response="#files-response" action="<?= admin_url('admin-ajax.php') ?>">

                <input type="hidden" name="cmd" value="saveSetting">
                <input type="hidden" name="key" value="filesFrequency">
                
                <table class="form-table">
                    <tr>
                        <th><label for="filesFrequency">Backup Frequency</label></th>
                        <td><?= $this->renderMenu('filesFrequency', $s['filesFrequency']) ?></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><button type="submit">Save</button></td>
                    </tr>
                </table>
            </form>
        </td>
        <td>
            <p>How often do you want to back up files?</p>
        </td>
    </tr>
</table>


<h2>Manual Backup</h2>
<table class="jbr-bu-foo">
    <tr>
        <td>
            <button class="jbr-bu-button" data-cmd="backupFilesNow" data-flash-response="#files-response">back up now</button>
        </td>
        <td>
            <p>Back up files now.</p>
        </td>
    </tr>
</table>


<?php
$lfb = $this->stampToDate($d['lastFilesBackup'], false);  # don't include time
?>
<h2>Set Timestamp</h2>
<table class="jbr-bu-foo">
    <tr>
        <td>
            
            <form class="jbr-bu-form" data-flash-response="#files-response" action="<?= admin_url('admin-ajax.php') ?>">

                <input type="hidden" name="cmd" value="saveLastFilesBackup">
                <table class="form-table">
                    <tr>
                        <th><label for="lastFilesBackup">Last File Backup</label></th>
                        <td><input id="lastFilesBackup" name="lastFilesBackup" type="text" value="<?= $lfb ?>" placeholder="yyyy-mm-dd"></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><button type="submit">Save</button></td>
                    </tr>
                </table>
            </form>
            
        </td>
    
    <td>
        <p>This is a little complicated... You probably want to use another method to initially back up all of your files, then use this just for the trickle of stuff that will come in the future.</p>
        </td>
    </tr>
</table>


<!--
<h2>File Backups</h2>
<form class="jbr-bu-form" data-show-response="#ff-response" action="<?= admin_url('admin-ajax.php') ?>">

    <input type="hidden" name="cmd" value="findFiles">
    <table class="form-table">
        <tr>
            <th><label for="subdir">Subdirectory</label></th>
            <td><input id="subdir" name="subdir" type="text" placeholder="/2016/05"></td>
        </tr>
        <tr>
            <th><label for="newerThan">Newer Than</label></th>
            <td><input id="newerThan" name="newerThan" type="text" placeholder="yyyy-mm-dd"></td>
        </tr>
        <tr>
            <td></td>
            <td><button type="submit">Find</button></td>
        </tr>
    </table>
</form>

<div id="ff-response" class="jbr-bu-output" style="height:300px"></div>
-->

