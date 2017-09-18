<?php $s = $this->getSettings() ?>

<div id="emails-response" class="jbr-bu-flash-response"></div>

<h2>Emails</h2>
<table class="jbr-bu-foo">
    <tr>
        <td>
            <form class="jbr-bu-form" data-flash-response="#emails-response" action="<?= admin_url('admin-ajax.php') ?>">

                <input type="hidden" name="cmd" value="saveSettings">
                <input type="hidden" name="keys" value="sendEmailTo,sendEmailWhen">
                
                <table class="form-table">
                    <tr>
                        <th><label for="sendEmailTo">Recipient(s)</label></th>
                        <td><input id="sendEmailTo" name="sendEmailTo" type="text" value="<?= $s['sendEmailTo'] ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="sendEmailWhen">When</label></th>
                        <td><?= $this->renderMenu('sendEmailWhen', $s['sendEmailWhen']) ?></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><button type="submit">Save</button></td>
                    </tr>
                </table>
            </form>
        </td>
        <td>
            <p>These people will be notified. Separate with a comma. The options are:</p>
            <dl>
                <dt>always</dt><dd>Send an email for every scheduled backup.</dd>
                <dt>errors</dt><dd>Send an email when there was a problem.</dd>
                <dt>never</dt><dd>Never send an email.</dd>
            </dl>
            <p>Note that we're only talking about scheduled backups, and not manual ones.</p>
        </td>
    </tr>
</table>
