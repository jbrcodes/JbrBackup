<?php 
use JbrBackup\JobLog; 
$recs = JobLog::GetRecent();
?>

<h2>Log</h2>

<table class="widefat striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Started</th>
            <th>Ended</th>
            <th>What</th>
            <th>How</th>
            <th>Result</th>
            <th>Log File</th>
        </tr>
    </thead>
    <tbody>
    <?php 
    foreach ($recs as $rec): 
        $c = $rec['result'];
        if ( !preg_match('!^OK\b!', $c) ) {
            $c = '<span class="error">' . $c . '</span>';
        }
    ?>
        <tr>
            <td><?= $rec['id'] ?></td>
            <td><?= $rec['startedStamp'] ?></td>
            <td><?= $rec['endedStamp'] ?></td>
            <td><?= $rec['what'] ?></td>
            <td><?= $rec['how'] ?></td>
            <td><?= $c ?></td>
            <td>
                <a href="<?= $rec['logFileUrl'] ?>">view</a>
                <?php if ($rec['what'] == 'files'): ?>
                    | <a href="<?= $rec['fileListUrl'] ?>">files</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>


<?php $s = $this->getSettings() ?>
<h2>Clean Up</h2>

<div id="cleanup-response" class="jbr-bu-flash-response"></div>

<table class="jbr-bu-foo">
    <tr>
        <td>
            <form class="jbr-bu-form" data-flash-response="#cleanup-response" action="<?= admin_url('admin-ajax.php') ?>">

                <input type="hidden" name="cmd" value="saveSetting">
                <input type="hidden" name="key" value="saveLogsFor">
                <table class="form-table">
                    <tr>
                        <th><label for="saveLogsFor">Save Logs For</label></th>
                        <td><input id="saveLogsFor" name="saveLogsFor" type="text" value="<?= $s['saveLogsFor'] ?>" placeholder="2 weeks"></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><button type="submit">save</button></td>
                    </tr>
                </table>
            </form>
            
        </td>
    
    <td>
        <p>You can specify how long logfiles should be kept. Older logfiles are
            regularly deleted. Choose a number and units, like <code>1 week</code> or <code>12 hours</code> or <code>2 months</code>.</p>
        </td>
    </tr>
</table>