<h2>Overview</h2>


<?php 
$info = $this->getSummaryInfo();
?>
<table class="widefat striped">
    <thead>
        <tr>
            <th>Type</th>
            <th>Frequency</th>
            <th>Last Backup</th>
            <th>Result</th>
            <th>Next Backup</th>
        </tr>
    </thead>
    
    <tbody>
        <tr>
            <td>db</td>
            <td><?= $info['dbFrequency'] ?></td>
            <td><?= $this->stampToDate($info['lastDbBackup']) ?></td>
            <td><?= $info['lastDbResult'] ?></td>
            <td><?= $this->stampToDate($info['nextDbBackup']) ?></td>
        </tr>
    
        <tr>
            <td>files</td>
            <td><?= $info['filesFrequency'] ?></td>
            <td><?= $this->stampToDate($info['lastFilesBackup']) ?></td>
            <td><?= $info['lastFilesResult'] ?></td>
            <td><?= $this->stampToDate($info['nextFilesBackup']) ?></td>
        </tr>
    
    
    </tbody>

</table>