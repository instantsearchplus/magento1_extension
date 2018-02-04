<?php
class Autocompleteplus_Autosuggest_Block_Adminhtml_Process extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    private $_pushConfig;

    protected function _toHtml()
    {
        $helper = Mage::helper('autocompleteplus_autosuggest');
        $this->_pushConfig=array(
        'styles' => array(
            'error' => array(

                'icon' => Mage::getDesign()->getSkinUrl('images/error_msg_icon.gif'),
                'bg'   => '#FDD'
            ),
            'message' => array(
                'icon' => Mage::getDesign()->getSkinUrl('images/fam_bullet_success.gif'),
                'bg'   => '#DDF'
            ),
            'loader'  => Mage::getDesign()->getSkinUrl('images/ajax-loader.gif')
        ),
        'template' => '<li style="#{style}" id="#{id}">'
        . '<img id="#{id}_img" src="#{image}" class="v-middle" style="margin-right:5px"/>'
        . '<span id="#{id}_status" class="text">#{text}</span>'
        . '</li>',
        'text'     => $this->__('Processed <strong>%s%% %s/%d</strong> records', '#{percent}', '#{updated}', $this->getBatchItemsCount()),
        'successText'  => $this->__('Imported <strong>%s</strong> records', '#{updated}')
        );

        echo '<html><head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
        echo '<script type="text/javascript">var FORM_KEY = "'.Mage::getSingleton('core/session')->getFormKey().'";</script>';

        $headBlock = $this->getLayout()->createBlock('page/html_head');
        $headBlock->addJs('prototype/prototype.js');
        $headBlock->addJs('mage/adminhtml/loader.js');
        echo $headBlock->getCssJsHtml();

        echo '<style type="text/css">
            ul { list-style-type:none; padding:0; margin:0; }
            li { margin-left:0; border:1px solid #ccc; margin:2px; padding:2px 2px 2px 2px; font:normal 12px sans-serif; }
            img { margin-right:5px; }
            </style>
            <title>'.$this->__('Syncing data...').'</title>
        </head><body>';
        echo '<ul>';
        echo '<li>';
            echo '<img src="'.Mage::getDesign()->getSkinUrl('images/note_msg_icon.gif').'" class="v-middle" style="margin-right:5px"/>';
            echo $this->__("Starting initial store product sync with InstantSearch+");
            echo '</li>';
            echo '<li style="background-color:#FFD;">';
            echo '<img src="'.Mage::getDesign()->getSkinUrl('images/fam_bullet_error.gif').'" class="v-middle" style="margin-right:5px"/>';
            echo $this->__("Warning: Please do not close this tab until sync is complete");

        echo '</li>';

        echo '<li id="liFinished" style="display:none;">
                    <img src="'.$this->getSkinUrl('images/note_msg_icon.gif').'" class="v-middle" style="margin-right:5px"/>
                    '.$this->__(" Initial Product Sync is finished. ").'
                    <span id="liFinished_count">0</span>&nbsp;'.$this->__("products were synced").'
              </li>';
        echo '</ul>';

        $pushId=$helper->getPushId();

        $totalPushes= Mage::getModel('autocompleteplus_autosuggest/pusher')->getCollection()->count();

        $pushUrl='';

        if($pushId!=''){
            $pushUrl=$helper->getPushUrl($pushId);
        }

echo '<script type="text/javascript">
                    var config= '.Mage::helper('core')->jsonEncode($this->_pushConfig).';
                    config.tpl = new Template(config.template);
                    config.tplTxt = new Template(config.text);
                    config.tplSccTxt = new Template(config.successText);

                    var url="'.$pushUrl.'";

                    var count=0;

                    if(url!=""){
                        sendImportData(url);
                    }else{
                        $("liFinished").show();
                        $("liFinished_count").update(count);
                        $("synced-rows").hide()
                    }



function sendImportData(url) {

    if (!$("updatedRows")) {
        Element.insert($("liFinished"), {before: config.tpl.evaluate({
            style: "background-color: #FFD;",
            image: config.styles.loader,
            text: "Syncing: push '.$pushId.'/'.$totalPushes.'",
            id: "updatedRows"
        })});
    }

    new Ajax.Request(url, {
      method: "get",
      onSuccess: function(transport) {

        if (transport.responseText.isJSON()) {
                var res=transport.responseText.evalJSON();

                if(!res){
                    Element.insert($("updatedRows"), {before: config.tpl.evaluate({
                        style: "background-color:"+config.styles.error.bg,
                        image: config.styles.error.icon,
                        text: res.message,
                        id: "error-"+makeid()
                    })});
                }else{

                if (!$("synced-rows")){
                    Element.insert($("updatedRows"), {before: config.tpl.evaluate({
                            style: "background-color:"+config.styles.message.bg,
                            image: config.styles.message.icon,
                            text: res.updatedSuccessStatus,
                            id: "synced-rows"
                        })});
                }else{
                    $("synced-rows_status").update(res.updatedSuccessStatus);
                }

                    url=res.nextPushUrl;

                    count+=res.count;

                    if(url!=""){
                        $("updatedRows_status").update(res.updatedStatus);
                        sendImportData(url);
                    }else{
                        $("liFinished").show();
                        $("liFinished_count").update(count);
                        $("updatedRows").hide()
                        $("synced-rows").hide()
                    }

                }
        } else {
            Element.insert($("updatedRows"), {before: config.tpl.evaluate({
                style: "background-color:"+config.styles.error.bg,
                image: config.styles.error.icon,
                text: transport.responseText.escapeHTML(),
                id: "error-"+makeid()
            })});
        }
      }
    });
}

function makeid()
{
    var text = "";
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

    for( var i=0; i < 5; i++ )
        text += possible.charAt(Math.floor(Math.random() * possible.length));

    return text;
}
</script>';

        echo '</body></html>';
    }

}