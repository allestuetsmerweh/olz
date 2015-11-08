/* eslint no-console: "off"*/
/* eslint no-empty: "off" */
/* global jQuery */
/* exported olzImageUpload */

window.olzImageUploadMaxWid = window.olzImageUploadMaxWid || 800
window.olzImageUploadMaxHei = window.olzImageUploadMaxHei || 800

window.olzOldIE = false
try {
  window.addEventListener('dragover', function(e) {
    e = e || event
    e.preventDefault()
  }, false)
  window.addEventListener('drop', function(e) {
    e = e || event
    e.preventDefault()
  }, true)
} catch (err) {
  window.olzOldIE = true
}

window.olzImageUploadState = 'IDLE'
window.olzImageUploadQueue = []
window.olzImageUploads = {}
window.olzImageUploadChanges = {}
window.olzImageUploadDragAttID = false
window.olzImageUploadDragTable = false

// TODO:
// - dynamic data => thumbs mapping (Ajax or else)
// - ajaxurl vs. OLZ Ajax

function olzImageUpload(elemID, multiple, imgIDs, ajaxURL, ajaxData, imgIDChangeCallback) {

  function olzImageUploadTemplateQueueElem(uqident) {
    return '<table style="display:inline-table; width:auto; margin:3px;" cellspacing="0"><tr><td style="width:128px; height:128px; padding:0px; border:0px;"><canvas width="128" height="128" style="margin:0px;" id="galerie-uqcanvas-' + uqident + '"></td></tr><tr><td style="width:128px; height:24px; padding:0px; border:0px;"><div style="text-align:center;">Bitte warten...</div></td></tr></table>'
  }

  function olzImageUploadUpdateQueue() {
    for (var i = 0; i < window.olzImageUploadQueue.length; i++) {
      var uqident = window.olzImageUploadQueue[i]
      var cnv = document.getElementById('galerie-uqcanvas-' + uqident)
      var img = window.olzImageUploads[uqident].thumb
      img = img || window.olzImageUploads[uqident].img
      if (cnv) {
        var ctx = cnv.getContext('2d')
        ctx.clearRect(0, 0, 128, 128)
        if (img) {
          var dwid = 128
          var dhei = img.height * dwid / img.width
          var xoff = 0
          var yoff = (128 - dhei) / 2
          if (img.width < img.height) {
            dhei = 128
            dwid = img.width * dhei / img.height
            yoff = 0
            xoff = (128 - dwid) / 2
          }
          ctx.drawImage(img, xoff, yoff, dwid, dhei)
          if (!window.olzImageUploads[uqident].thumb) {
            var imgcnvtmp = document.createElement('img')
            imgcnvtmp.src = cnv.toDataURL()
            window.olzImageUploads[uqident].thumb = imgcnvtmp
          }
        } else {
          ctx.fillStyle = 'rgb(240,240,240)'
          ctx.fillRect(0, 0, 128, 128)
        }
        var colordict = {
          0: '150,150,150',
          1: '255,180,0',
          2: '0,230,0',
          3: '255,0,0',
        }
        var phase = window.olzImageUploads[uqident].phase
        var phasebase = Math.floor(phase)
        var phaseoff = phase - phasebase
        ctx.fillStyle = 'rgb(0,0,0)'
        ctx.beginPath()
        ctx.arc(95, 95, 15, 0, 2 * Math.PI)
        ctx.fill()
        ctx.fillStyle = 'rgba(' + colordict[phasebase + 1] + ',0.5)'
        ctx.beginPath()
        ctx.moveTo(95, 95)
        ctx.arc(95, 95, 15, 1.5 * Math.PI, (1.5 + 2 * phaseoff) * Math.PI)
        ctx.lineTo(95, 95)
        ctx.fill()
        ctx.fillStyle = 'rgb(' + colordict[phasebase + 1] + ')'
        ctx.beginPath()
        ctx.moveTo(95, 95)
        ctx.arc(95, 95, 14, 1.5 * Math.PI, (1.5 + 2 * phaseoff) * Math.PI)
        ctx.lineTo(95, 95)
        ctx.fill()
        ctx.fillStyle = 'rgba(' + colordict[phasebase] + ',0.5)'
        ctx.beginPath()
        ctx.moveTo(95, 95)
        ctx.arc(95, 95, 15, (1.5 + 2 * phaseoff) * Math.PI, (1.5 + 2) * Math.PI)
        ctx.lineTo(95, 95)
        ctx.fill()
        ctx.fillStyle = 'rgb(' + colordict[phasebase] + ')'
        ctx.beginPath()
        ctx.moveTo(95, 95)
        ctx.arc(95, 95, 14, (1.5 + 2 * phaseoff) * Math.PI, (1.5 + 2) * Math.PI)
        ctx.lineTo(95, 95)
        ctx.fill()
      }
    }
  }

  function olzImageUploadFile() {
    if (!window.olzImageUploadQueue) { // Nothing in the queue
      return
    }

    var uqindex = -1
    var numuploading = 0
    for (var i = 0; i < window.olzImageUploadQueue.length; i++) {
      var phase = window.olzImageUploads[window.olzImageUploadQueue[i]].phase
      if (phase == 0 && uqindex == -1) {
        uqindex = i
      } else if (0 < phase && phase <= 1) {
        numuploading++
        if (4 < numuploading) { // 5 images are being uploaded in parallel, that's enough => cancel
          return
        }
      }
    }
    if (uqindex == -1) { // No images ready to upload
      if (window.olzImageUploadState == 'LOADED' && numuploading == 0) { // All images read and resized and none uploading => Queue completely processed
        setTimeout(function () {
          window.olzImageUploadState = 'IDLE'
          window.olzImageUploadQueue = []
          window.olzImageUploads = {}
          olzImageUploadRedraw()
        }, 1000)
      }
      return
    }
    var uqident = window.olzImageUploadQueue[uqindex]
    window.olzImageUploads[uqident].phase = 0.25
    olzImageUploadUpdateQueue()
    var canvas = window.olzImageUploads[uqident].img

    var base64 = false
    try {
      base64 = canvas.toDataURL('image/jpeg')
    } catch(err) {
      base64 = canvas.toDataURL()
    }
    var chunk_size = 1024 * 32 // Chunk into 32KB
    var b64arr = []
    for (var j = 0; j < base64.length; j += chunk_size) {
      b64arr.push(base64.substr(j, chunk_size))
    }
    var ajax_upload = function (uqident, b64arr, part) {
      var data = ajaxData || {}
      data.action = data.action || 'image_upload'
      data.uqident = uqident
      data.content = b64arr[part]
      data.part = part
      data.last = b64arr.length - 1 <= part ? '1' : '0'
      jQuery.post(ajaxURL, data).done(function (response) {
        var resp = ['NOK']
        try {
          resp = JSON.parse(response)
        } catch (err) {}
        if (resp[0] != 'OK') { // This may be useful, as some hosting providers have POST formular checking security policies. Base64-encode again => it may work
          console.error('Error uploading')
          return
        }
        if (part + 1 < b64arr.length) { // Still parts to upload
          window.olzImageUploads[uqident].phase = 0.25 + 0.75 * (part + 1) / b64arr.length
          olzImageUploadUpdateQueue()
          ajax_upload(uqident, b64arr, part + 1)
          return
        }
        window.olzImageUploads[uqident].phase = (resp[0] == 'OK' ? 2 : 3)
        if (resp[0] == 'OK') {
          if (multiple) {
            imgIDs.push(resp[1])
          } else {
            imgIDs = [resp[1]]
          }
          imgIDChangeCallback(imgIDs)
        }
        olzImageUploadUpdateQueue()
        setTimeout(function () {
          olzImageUploadFile()
        }, 1)
      }).fail(function () {
        window.olzImageUploads[uqident].phase = 3
        olzImageUploadUpdateQueue()
        setTimeout(function () {
          olzImageUploadFile()
        }, 1)
      })
    }
    ajax_upload(uqident, b64arr, 0)
  }
  function olzImageUploadLoadFile(files, ind) {
    if (!ind) {
      var dropzone = document.getElementById('galerie-dropzone')
      dropzone.innerHTML = '<b>Bitte warten</b>, <br>Bilder werden gelesen und verkleinert...'
      dropzone.ondrop = function () {}
      var fileselect = document.getElementById('galerie-fileselect')
      if (fileselect) {
        fileselect.disabled = 'disabled'
      }
      ind = 0
      window.olzImageUploadState = 'LOADING'
    }
    var file = files[ind]
    var uqident = 'id' + (new Date().getTime()) + '-' + Math.random() + '-' + ind
    var reader = new FileReader()
    reader.onload = function (reader, uqident, ind) {
      var img = document.createElement('img')
      img.onerror = function (img, res, ind) {
        if (res.match(/^data:image\/(jpg|jpeg|png)/i)) {
          alert('"' + files[ind].name + '" ist ein beschädigtes Bild, bitte wähle ein korrektes Bild aus. \nEin Bild hat meist die Endung ".jpg", ".jpeg" oder ".png".')
        } else {
          alert('"' + files[ind].name + '" ist kein Bild, bitte wähle ein Bild aus. \nEin Bild hat meist die Endung ".jpg", ".jpeg" oder ".png".')
        }
        ind++
        if (ind < files.length) {
          setTimeout(function () {
            olzImageUploadLoadFile(files, ind)
          }, 1)
        } else {
          window.olzImageUploadState = 'LOADED'
          setTimeout(function () {
            olzImageUploadFile()
          }, 1)
        }
        img.onload = false // To be sure that memory can be released
        img.onerror = false // To be sure that memory can be released
      }.bind(this, img, reader.result, ind)
      img.onload = function (img, uqident, ind) {
        var owid = img.width
        var ohei = img.height
        var wid = owid
        var hei = ohei
        var max_wid = window.olzImageUploadMaxWid
        var max_hei = window.olzImageUploadMaxHei
        if (ohei / owid < max_hei / max_wid) {
          if (max_wid < owid) {
            wid = max_wid
            hei = wid * ohei / owid
          }
        } else {
          if (max_hei < ohei) {
            hei = max_hei
            wid = hei * owid / ohei
          }
        }
        var canvas = document.createElement('canvas')
        canvas.width = wid
        canvas.height = hei

        // ### Browser-native scaling ###
        var max2scale = function (srcImg, dstImg) {
          var max2img = srcImg
          if (dstImg.width * 2 < owid && dstImg.height * 2 < ohei) {
            var bigcanvas = document.createElement('canvas')
            bigcanvas.width = dstImg.width * 2
            bigcanvas.height = dstImg.height * 2
            var bigctx = bigcanvas.getContext('2d')
            bigctx.drawImage(srcImg, 0, 0, dstImg.width * 2, dstImg.height * 2)
            max2img = bigcanvas
          }
          var ctx = canvas.getContext('2d')
          ctx.drawImage(max2img, 0, 0, dstImg.width, dstImg.height)
        }
        max2scale(img, canvas)
        // #########

        window.olzImageUploadQueue.push(uqident)
        window.olzImageUploads[uqident] = {
          img: canvas,
          thumb: false,
          phase: 0,
        }
        ind++
        var uqelem = document.getElementById('galerie-uploadqueue')
        var div = document.createElement('div')
        div.innerHTML = olzImageUploadTemplateQueueElem(uqident)
        for (var i = 0; i < div.childNodes.length; i++) {
          uqelem.appendChild(div.childNodes[i])
        }
        olzImageUploadUpdateQueue()
        if (ind < files.length) {
          setTimeout(function () {
            olzImageUploadLoadFile(files, ind)
          }, 250) // This uses much CPU => 0.25s break between
        } else {
          window.olzImageUploadState = 'LOADED'
        }
        setTimeout(function () {
          olzImageUploadFile()
        }, 1)
        img.onload = false // To be sure that memory can be released
        img.onerror = false // To be sure that memory can be released
      }.bind(this, img, uqident, ind)
      img.src = reader.result
      reader.onload = false // To be sure that memory can be released
    }.bind(this, reader, uqident, ind)
    reader.readAsDataURL(file)
  }

  function olzImageUploadRedraw() {
    var resizesupport = window.File && window.FileReader && window.FileList && window.Blob && document.createElement('canvas') && !window.olzOldIE
    var htmlout = ''
    for (var i = 0; i < imgIDs.length; i++) {
      htmlout += '<div>' + imgIDs[i] + '</div>'
    }
    jQuery('#' + elemID).html(htmlout)
    jQuery.post(ajaxURL, {
      action: 'thumb_url_from_attachment_id',
      imgIDs: JSON.stringify(imgIDs),
    }, function (response) {
      var tmp = JSON.parse(response)
      var htmlout = ''
      for (var i = 0; i < imgIDs.length; i++) {
        htmlout += '<table cellspacing="0" id="galerie-table-' + imgIDs[i] + '"><tr><td class="upper"><div id="galerie-imgdiv-' + imgIDs[i] + '"><img src="' + tmp[imgIDs[i]] + '" style="width:128px; height:128px;" id="galerie-img-' + imgIDs[i] + '" /></div></td></tr>'
        htmlout += '<tr><td class="lower"><img src="' + window.olzThemeURL + '/icns/rotate-cw.png" alt="R" title="90 im Uhrzeigersinn drehen" style="width:16px; height:16px; border:0px;" id="galerie-rotate-' + imgIDs[i] + '"> <img src="' + window.olzThemeURL + '/icns/trash.png" alt="L" title="Bild löschen" style="width:16px; height:16px; border:0px;" id="galerie-delete-' + imgIDs[i] + '"></td></tr></table>'
      }
      htmlout += '<span id="galerie-uploadqueue">'
      for (var j = 0; j < window.olzImageUploadQueue.length; j++) {
        htmlout += olzImageUploadTemplateQueueElem(window.olzImageUploadQueue[j])
      }
      htmlout += '</span>'
      var instruction = 'Bilder zum Hinzufügen hierher ziehen'
      if (!multiple) {
        if (imgIDs.length == 0) {
          instruction = 'Bild zum Hinzufügen hierher ziehen'
        } else{
          instruction = 'Bild zum Ersetzen hierher ziehen'
        }
      }
      htmlout += '<table cellspacing="0"><tr><td class="upper"><div style="width:112px; height:122px; background-color:rgb(240,240,240); border:3px dashed rgb(180,180,180); border-radius:10px; padding:0px 5px; text-align:center; white-space:normal;" id="galerie-dropzone">' + instruction + '</div></td></tr><tr><td class="lower"><input type="file"' + (multiple ? ' multiple="multiple"' : '') + ' style="width:128px; border:0px;" id="galerie-fileselect"></td></tr></table>'
      jQuery('#' + elemID).html(htmlout)
      if ((imgIDs.length + 2) * 180 < jQuery('#' + elemID).height()) {
        jQuery('#' + elemID).html('<div style="background-color:rgb(255,0,0); border:2px solid rgb(200,0,0); padding:10px;">Ihr Browser ist zu alt, um den Inhalt dieser Seite sinnvoll anzuzeigen. Bitte aktualisieren Sie Ihren Browser!</div>')
        return
      }
      olzImageUploadUpdateQueue()
      var dropzone = document.getElementById('galerie-dropzone')
      if (resizesupport) {

        dropzone.ondragover = function (dropzone) {
          dropzone.style.backgroundColor = 'rgb(220,220,220)'
          dropzone.style.borderColor = 'rgb(150,150,150)'
        }.bind(this, dropzone)
        dropzone.ondragleave = function (dropzone) {
          dropzone.style.backgroundColor = 'rgb(240,240,240)'
          dropzone.style.borderColor = 'rgb(180,180,180)'
        }.bind(this, dropzone)
        dropzone.ondrop = function (dropzone, e) {
          dropzone.style.backgroundColor = 'rgb(240,240,240)'
          dropzone.style.borderColor = 'rgb(180,180,180)'
          if (!e.dataTransfer) {
            return
          }
          var files = e.dataTransfer.files
          if (!files) {
            return
          }
          setTimeout(function (files) {
            olzImageUploadLoadFile(files)
          }.bind(this, files), 1)
        }.bind(this, dropzone)
        dropzone.onmouseup = function (dropzone) {
          dropzone.style.backgroundColor = 'rgb(240,240,240)'
          dropzone.style.borderColor = 'rgb(180,180,180)'
        }.bind(this, dropzone)
      } else {
        dropzone.style.backgroundColor = 'rgb(250,250,250)'
        dropzone.style.borderColor = 'rgb(220,220,220)'
        dropzone.style.color = 'rgb(220,220,220)'
        dropzone.innerHTML = 'Drag&Drop unmöglich.<br>Knopf unten verwenden.'
      }
      var fileselect = document.getElementById('galerie-fileselect')
      if (resizesupport) {
        fileselect.onchange = function (fileselect) {
          var files = fileselect.files
          if (!files) {
            return
          }
          olzImageUploadLoadFile(files)
        }.bind(this, fileselect)
      } else {
        console.error('Resizing not supported')
      }
      for (var k = 0; k < imgIDs.length; k++) {
        var attID = imgIDs[k]
        var droptableelem = document.getElementById('galerie-table-' + attID)
        droptableelem.onmousemove = function (attID, droptableelem, e) {
          var dragtableelem = window.olzImageUploadDragTable
          if (dragtableelem && dragtableelem != droptableelem) {
            var rect = droptableelem.getBoundingClientRect()
            var parent = dragtableelem.parentElement
            if (e.clientX - rect.left - rect.width / 2 < 0) {
              parent.insertBefore(dragtableelem, document.getElementById('galerie-table-' + attID))
            } else {
              parent.insertBefore(dragtableelem, document.getElementById('galerie-table-' + attID).nextSibling)
            }
          }
        }.bind(this, attID, droptableelem)
        var tableelem = document.getElementById('galerie-table-' + attID)
        var imgelem = document.getElementById('galerie-img-' + attID)
        var imgdivelem = document.getElementById('galerie-imgdiv-' + attID)
        imgdivelem.onmouseup = function (attID, imgelem, tableelem, e) {
          e = e || event
          e.preventDefault()
          console.log('TODO: Actually move')
          window.olzImageUploadDragAttID = false
          window.olzImageUploadDragTable = false
        }.bind(this, attID, imgelem, tableelem)
        imgdivelem.onmousedown = function (attID, imgelem, tableelem, e) {
          e = e || event
          e.preventDefault()
          window.olzImageUploadDragAttID = attID
          window.olzImageUploadDragTable = tableelem
        }.bind(this, attID, imgelem, tableelem)
        imgelem.onload = function (imgelem) {
          var wid = imgelem.offsetWidth
          var hei = imgelem.offsetHeight
          if (hei < wid) {
            imgelem.style.width = '128px'
            imgelem.style.marginTop = Math.round((1 - hei / wid) * 128 / 2) + 'px'
          } else {
            imgelem.style.height = '128px'
            imgelem.style.marginLeft = Math.round((1 - wid / hei) * 128 / 2) + 'px'
          }
        }.bind(this, imgelem)
        var updatecontrols = function () {
          // TODO: maybe remove?
        }
        var rotelem = document.getElementById('galerie-rotate-' + attID)
        rotelem.onclick = function (attID) {
          var elem = document.getElementById('galerie-imgdiv-' + attID)
          if (elem.style.transform == undefined &&
            elem.style.webkitTransform == undefined &&
            elem.style.msTransform == undefined) {
            alert('Diese Funktion wird von Ihrem Browser leider nicht unterstützt. Installieren Sie einen modernen Browser!')
          }
          var changes = window.olzImageUploadChanges[attID] || {}
          var rt = changes.rotation || 0
          rt = (rt + 90) % 360
          elem.style.transform = 'rotate(' + rt + 'deg)'
          elem.style.webkitTransform = 'rotate(' + rt + 'deg)'
          elem.style.msTransform = 'rotate(' + rt + 'deg)'
          changes.rotation = rt
          window.olzImageUploadChanges[attID] = changes
          updatecontrols()
        }.bind(this, attID)
        var delelem = document.getElementById('galerie-delete-' + attID)
        delelem.onclick = function (attID) {
          var changes = window.olzImageUploadChanges[attID] || {}
          var dl = changes.deletion || 0
          dl = !dl
          if (dl) {
            for (var i = 0; i < imgIDs.length; i++) {
              if (imgIDs[i] == attID) {
                imgIDs.splice(i, 1)
                imgIDChangeCallback(imgIDs)
                break
              }
            }
          }
          document.getElementById('galerie-imgdiv-' + attID).style.visibility = dl ? 'hidden' : 'visible'
          changes.deletion = dl
          window.olzImageUploadChanges[attID] = changes
          updatecontrols()
        }.bind(this, attID)
      }
    })
  }

  olzImageUploadRedraw()
}
