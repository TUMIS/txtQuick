<?php
include_once 'app/lib.php';
include_once APP_ROOT . '/app/auth.php';

?><!DOCTYPE html>
<html lang="en">
  <head>
  <meta charset="utf-8">
  <title>txtQuick</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="">
  <meta name="author" content="">
  <link href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css" rel="stylesheet">
  <!--[if lt IE 9]>
  <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->
  <link rel="shortcut icon" href="/static/img/favicon.ico">
  <link rel="apple-touch-icon" sizes="144x144" href="/static/img/apple-touch-icon.png">
  <script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
  <script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>
  <script src="/static/js/moment.min.js"></script>
  <script type="text/javascript">
  $(document).ready(function() {
    function getPosts( offset ) {
      if(!offset) var offset = 0;

      $.getJSON('/app/msg.php',{"o": offset }, function(data) {
        //console.log(data);
        var items = [];

        $.each(data, function(key, post) {

          var m = moment.unix(post.Posted);

          items.push
          (
          '<tr>'
          + '<td title="'+ m.format("MMMM Do YYYY, h:mm:ss a") +'" datetime="'+ post.Posted +'">'+ m.calendar() +'</td>'
          + '<td>' + post.Body + '</td>'
          + '<td>' + post.Phone + '</td>'
          + '<td>' + post.FromCity + '</td>'
          + '<td>' + post.FromZip + '</td>'
          + '</tr>'
          );
        });

        var html = items.join('');

        if (offset == 0) {
          $('#responses').append( html );
        }

        $('.badge').text( items.length );
    });

    return false;
  }

  //get first batch
  getPosts();

  //e-z infinite scroll
  var offset = 12;
  $(window).scroll(function() {
    if ($(window).scrollTop() == $(document).height() - $(window).height()) {
      getPosts(offset);
      offset = offset + 12;
    }
  });

  //lastload timer
  var loaded = new Date();
  setInterval(function(){
    $('#lastload').text( moment(loaded).fromNow() );
  }
  ,21*1000);

  //edit reply
  $('#systemmsg').hide();
  $("#saveReply").bind("click mousedown touchdown", function(e) {

    e.preventDefault();
    var data = {};
    data.replytext = $('#replytext').val();

    $.ajax({
      type: 'POST',
      url: '/app/editor.php',
      data: data,
      dataType: 'text',
      cache: false,
      beforeSend: function() {
        $(this).attr("disable"); //disable double submit
      },
      success: function(response) {
        if ( response == 'ok' ) {
          $('#systemmsg').text('New reply saved').show().fadeOut(5000);
          $('#myModal').modal('hide');
        }
      },
      error: function(xhr,text,error) {
        $(this).attr("enable"); //enable resubmit
        alert("There was an error saving, please try again.");
      }
    }); 
  });

  });
  </script>
  <style>
  .bs-docs-nav{
    text-shadow: 0 -1px 0 rgba(0,0,0,.15);
    background-color: #563d7c;
    border-color: #463265;
    box-shadow: 0 1px 0 rgba(255,255,255,.1);
  }
  .bs-docs-nav .navbar-brand,
  .navbar-text {
    color: #fff;
  }
  .navbar{
    border-radius: 0;
  }
  #lastload{
    opacity: .667;
  }
  </style>
  </head>
  <body>
  
  <header class="navbar bs-docs-nav" role="banner">
    <div class="navbar-brand"><span class="glyphicon glyphicon-inbox"></span> Responses</div>
    <small class="navbar-text pull-right" id="lastload"></small>
  </header>

  <div class="container container-fluid">
    <div class="pull-right"><a data-toggle="modal" href="#myModal" class="btn btn-info btn-xs">Edit Reply</a></div>
    <div class="alert alert-info" id="systemmsg">stuff</div>
  </div>

  <div class="container container-fluid">
    <div class="table-responsive">
      <table class="table table-hover table-condensed table-striped">
        <thead>
        <th title="Posted"><span class="glyphicon glyphicon-calendar"></span></th>
        <th title="Body"><span class="glyphicon glyphicon-envelope"></span> <span class="badge"></span></th>
        <th title="Phone"><span class="glyphicon glyphicon-phone-alt"></span></th>
        <th title="City"><span class="glyphicon glyphicon-home"></span></th>
        <th title="Zip Code"><span class="glyphicon glyphicon-barcode"></span></th>
        </thead>
        <tbody id="responses"></tbody>
      </table>
    </div>
  </div>

  <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
          <h4 class="modal-title">Edit the Txt Reply</h4>
        </div>
        <div class="modal-body">
          <small class="pull-right text-muted">( max: 160 characters )</small>
          <textarea name="replytext" class="form-control" rows="3" id="replytext"><?= TxtQuick_SMS::get_reply() ?></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" id="saveReply">Save Changes</button>
        </div>
      </div>
    </div>
  </div>

  </body>
</html>
