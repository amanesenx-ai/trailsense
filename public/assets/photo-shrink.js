/* AC7.5 — phone photos (4–8 MB from an iPhone) are resized in the browser before upload:
   longest side ≤ 1600 px, JPEG ~0.85 → usually 300–600 KB. Faster on mobile data, and well under the server limit.
   If anything fails (old browser, unreadable file) the original file is sent and the server resizes it (PhotoResizer). */
(function () {
  'use strict';
  var MAX_SIDE = 1600, QUALITY = 0.85;
  var input = document.querySelector('input[type=file][data-shrink]');
  if (!input || !window.DataTransfer || !HTMLCanvasElement.prototype.toBlob) return;
  var status = document.getElementById('photo-status');
  var form = input.form, submit = form && form.querySelector('[type=submit]');

  function say(t) { if (status) status.textContent = t; }
  function mb(n) { return (n / 1048576).toFixed(1) + ' MB'; }

  input.addEventListener('change', function () {
    var file = input.files && input.files[0];
    if (!file || !/^image\//.test(file.type) || file.size < 600 * 1024) { say(''); return; }
    say('Preparing the photo…');
    if (submit) submit.disabled = true;
    var url = URL.createObjectURL(file), img = new Image();
    function done(msg) { URL.revokeObjectURL(url); if (submit) submit.disabled = false; say(msg); }
    img.onerror = function () { done(''); };
    img.onload = function () {
      // browsers apply the EXIF rotation when drawing (image-orientation: from-image), so the result is upright
      var scale = Math.min(1, MAX_SIDE / Math.max(img.naturalWidth, img.naturalHeight));
      var c = document.createElement('canvas');
      c.width = Math.round(img.naturalWidth * scale); c.height = Math.round(img.naturalHeight * scale);
      var g = c.getContext('2d'); g.fillStyle = '#fff'; g.fillRect(0, 0, c.width, c.height);
      g.drawImage(img, 0, 0, c.width, c.height);
      c.toBlob(function (blob) {
        if (!blob || blob.size >= file.size) { done(''); return; }
        var name = file.name.replace(/\.[^.]+$/, '') + '.jpg';
        var dt = new DataTransfer();
        dt.items.add(new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }));
        input.files = dt.files;
        done('✓ Photo ready (' + mb(file.size) + ' → ' + mb(blob.size) + ')');
      }, 'image/jpeg', QUALITY);
    };
    img.src = url;
  });
})();
