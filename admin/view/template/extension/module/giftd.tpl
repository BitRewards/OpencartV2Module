<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
     <div class="page-header">
        <div class="container-fluid">
          <div class="pull-right">
            <button type="submit" onclick="$('#form').submit();" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
            <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
          <h1><?php echo $heading_title; ?></h1>
          <ul class="breadcrumb">
            <?php foreach ($breadcrumbs as $breadcrumb) { ?>
            <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
            <?php } ?>
          </ul>
        </div>
     </div>
     <div class="container-fluid">
        <?php if ($error_warning) { ?>
        <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
          <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php } ?>
      <div class="panel panel-default">
          <div class="panel-heading">
            <h3 class="panel-title"><i class="fa fa-pencil"></i></h3>
          </div>
          <div class="panel-body">
              <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form"  class="form-horizontal">
                 <div class="form-group">
                      <label class="col-sm-2 control-label" for="input-api_key"><?php echo $text_api_key; ?></label>
                      <div class="col-sm-10">
                        <input type="text" id="input-api_key" class="form-control" name="giftd_api_key" value="<?php echo $giftd_api_key ?>" size="20"/>
                      </div>
                    </div>
                 <div class="form-group">
                      <label class="col-sm-2 control-label" for="input-user_id"><?php echo $text_user_id; ?></label>
                      <div class="col-sm-10">
                        <input type="text" id="input-user_id" class="form-control" name="giftd_user_id" value="<?php echo $giftd_user_id ?>" size="20" />
                      </div>
                 </div>
                 <div class="add_params form-group">
                      <label class="col-sm-2 control-label" for="input-partner_code"><?php echo $text_partner_code; ?></label>
                      <div class="col-sm-10">
                        <input type="text" id="input-partner_code" class="form-control" name="giftd_partner_code" value="<?php echo $giftd_partner_code ?>" size="20" />
                      </div>
                 </div>
                 <div class="add_params form-group">
                      <label class="col-sm-2 control-label" for="input-prefix"><?php echo $text_prefix; ?></label>
                      <div class="col-sm-10">
                        <input type="text" id="input-prefix" class="form-control" name="giftd_prefix" value="<?php echo $giftd_prefix ?>" size="20" />
                        <input type="hidden" name="giftd_code_updated" value="<?php echo $giftd_code_updated ?>" size="20" />
                        <textarea style="display: none;" cols="" name="giftd_js_code" rows=""><?php echo $giftd_js_code ?></textarea>
                      </div>
                 </div>
              </form>
          </div>
      </div>
     </div>
</div>
<script type="text/javascript"><!--
function check_data(){
    var api_key = $('input[name="giftd_api_key"]').val();
    var user_id = $('input[name="giftd_user_id"]').val();
    
    if((api_key != '') && (user_id != '')){
        $(".add_params").show();
    }else{
        $(".add_params").hide();
    }
}

$(document).ready(function(){
    check_data()
});

$('input[name="giftd_api_key"]').blur(function(){
    check_data()
});

$('input[name="giftd_user_id"]').blur(function(){
    check_data()
});
//--></script> 
<?php echo $footer; ?>