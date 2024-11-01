(function($) {
  $(document).ready(function() {
    $('#zuzu_hot_not li').click(function() {
      var unreact = ($(this).hasClass("clicked") ? true : false);
      var id = $(this).parent().data("postId") || $(this).parent().data("post-id");
      var url = zhn_data.ajax_url;
      var decision = $(this).data().decision;
      $.post(url, { postid: id, action: 'zhn_react', decision: decision, unreact: unreact }, function(data) {
        console.log("Ajax: " + data);
      });

      $(this).toggleClass("clicked");

      var howMany = parseInt($(this).find('span').text());
      if (howMany > 0) {
        if ($(this).hasClass("clicked")) {
          howMany += 1;
        } else {
          howMany -= 1;
        }
      } else {
        howMany = 1;
      }
      $(this).find('span').text(howMany);

    });


  });

})(jQuery);

document.addEventListener("touchstart", function() {}, true);

if ('createTouch' in document) {
  try {
    var ignore = /:hover/;
    for (var i = 0; i < document.styleSheets.length; i++) {
      var sheet = document.styleSheets[i];
      for (var j = sheet.cssRules.length - 1; j >= 0; j--) {
        var rule = sheet.cssRules[j];
        if (rule.type === CSSRule.STYLE_RULE && ignore.test(rule.selectorText)) {
          sheet.deleteRule(j);
        }
      }
    }
  } catch (e) {}
}
