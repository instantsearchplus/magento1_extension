<?php
/** @var Autocompleteplus_Autosuggest_Block_Notifications $this */
?>
<?php $notifications = $this->getNotifications(); ?>
<?php if ($notifications->count()): ?>
    <?php foreach ($notifications as $notification): ?>
        <div id="autosuggest-notification<?php echo $notification->getId() ?>" class="notification-global">
            <?php echo $notification->getMessage() ?>.
            <a href="#" onclick="autosuggestRemove('<?php echo $notification->getId() ?>'); return false;">
                <?php echo  $this->__('Remove this notification'); ?>
            </a>
        </div>
    <?php endforeach; ?>
    <script type="text/javascript">
        function autosuggestRemove(notification_id) {
            var url = '<?php echo $this->getUrl('*/autocompleteplus/notification', array('_current' => true)); ?>';
            new Ajax.Request(url, {
                method: 'post',
                parameters: {notification_id: notification_id},
                onComplete: function(transport) {
                    if (200 == transport.status) {
                        $('autosuggest-notification'+ notification_id).remove();
                    }
                }
            });
        }
    </script>
<?php endif; ?>