window.onload = function() {
  //<editor-fold desc="Changeable Configuration Block">
  const dataUrl =  document.getElementById("swagger-ui").attributes["data-url"].value;
  // the following lines will be replaced by docker/configurator, when it runs in a docker-container
  window.ui = SwaggerUIBundle({
    url: dataUrl,
    dom_id: '#swagger-ui',
    deepLinking: true,
    operationsSorter: 'method',
    presets: [
      SwaggerUIBundle.presets.apis,
    ],
    plugins: [
      SwaggerUIBundle.plugins.DownloadUrl
    ],
  });

  //</editor-fold>
};
