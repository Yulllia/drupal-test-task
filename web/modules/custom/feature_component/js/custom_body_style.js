(function (Drupal, drupalSettings) {
    Drupal.behaviors.customBodyStyle = {
      attach: function (context, settings) {
        if (context === document) {  
          const bodyBackground = settings.feature_component?.bodyBackground;
          document.body.style.backgroundColor = bodyBackground;
        }
      },
    };
  })(Drupal, drupalSettings);