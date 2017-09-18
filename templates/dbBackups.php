<?php $s = $this->getSettings() ?>

<div id="db-backups-response" class="jbr-bu-flash-response"></div>

<h2>DB Backup Frequency</h2>
<table class="jbr-bu-foo">
    <tr>
        <td>
         
            <form class="jbr-bu-form" data-flash-response="#db-backups-response" action="<?= admin_url('admin-ajax.php') ?>">

                <input type="hidden" name="cmd" value="saveSetting">
                <input type="hidden" name="key" value="dbFrequency">
                
                <table class="form-table">
                    <tr>
                        <th><label for="dbFrequency">Backup Frequency</label></th>
                        <td><?= $this->renderMenu('dbFrequency', $s['dbFrequency']) ?></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><button type="submit">Save</button></td>
                    </tr>
                </table>
            </form>
        </td>
        <td>
            <p>How often do you want to back up the DB?</p>
        </td>
    </tr>
</table>


<h2>Manual Backup</h2>
<table class="jbr-bu-foo">
    <tr>
        <td>
            <button class="jbr-bu-button" data-cmd="backupDbNow" data-flash-response="#db-backups-response">back up now</button>
        </td>
        <td>
        <p>Back up the DB now.</p>
        </td>
    
    </tr>


</table>