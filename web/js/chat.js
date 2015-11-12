$(function(){
  $('#messages')[0].scrollTop = $('#messages')[0].scrollHeight;
  var socket = io.connect('http://127.0.0.1:8080/');
  socket.on('update',function(data) {
    var d = new Date(data.id);
    var $template = $($('#message-template').html());
    $template.find('*[data-profile]').html(data.from.nickname +  " | " +  d.toLocaleString());
    $template.find('*[data-msg]').html(data.msg);
    $template.find('*[data-img]').attr('src', "http://www.gravatar.com/avatar/" + data.from.hash + "?d=mm&s=24");
    $('#messages').append($template);
    $('#messages')[0].scrollTop = $('#messages')[0].scrollHeight;
  });

  socket.on('joined', function(data){
    var d = new Date(data.time);
    var $template = $($('#joined-template').html());
    $template.find('*[data-profile]').html(data.from.nickname +  " joined | " +  d.toLocaleString());
    $template.find('*[data-img]').attr('src', "http://www.gravatar.com/avatar/" + data.from.hash + "?d=mm&s=24");
    $('#messages').append($template);
    $('#messages')[0].scrollTop = $('#messages')[0].scrollHeight;
  });

  $('#message-form').submit(function(event){
    $.post('/send', {
      message: $('#message').val(),
    });
    $('#message').val('');
    return false;
  });
});