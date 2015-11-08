/* eslint no-console: "off"*/
/* eslint no-empty: "off" */
/* global jQuery, QRCode, ajaxurl */
/* exported buildUserProfileEditor */

var formatUserProfileFields = {
  'tel': function (str) {
    var matches = /^(00[0-9]{2}|\+[0-9]{2}|0)([0-9]{2})([0-9]{3})([0-9]{2})([0-9]{2})$/.exec(str)
    return matches[1] + (matches[1] === '0' ? '' : ' ') + matches[2] + ' ' + matches[3] + ' ' + matches[4] + ' ' + matches[5]
  },
  'address': function (str) {return str},
  'plz': function (str) {return str},
  'city': function (str) {return str},
  'birthday': function (str) {return str.substr(8, 2) + '.' + str.substr(5, 2) + '.' + str.substr(0, 4)},
  'si-card-number': function (str) {return str},
}

function buildUserProfileEditor(data, regexes, botName) {
  var additionalTableRows = jQuery('#additional-fields').find('tr')
  jQuery('.user-email-wrap').parent().append(additionalTableRows)
  jQuery('#additional-fields').remove()
  for (var ident in data) {
    jQuery('#' + ident).val(formatUserProfileFields[ident](data[ident]))
    jQuery('#' + ident).on('change blur', function (ident, e) {
      var isValid = true
      if (ident in regexes) {
        isValid = new RegExp(regexes[ident]).exec(e.target.value)
      }
      if (isValid) {
        jQuery(e.target).css({ borderColor: 'rgb(0,180,0)' })
      } else {
        jQuery(e.target).css({ borderColor: 'rgb(220,0,0)' })
      }
    }.bind(this, ident))
  }
  jQuery('#telegram-text').show()
  jQuery('#telegram-link').css({ cursor: 'pointer' }).on('click', configureTelegram.bind(this, botName))
}

function showModalDialog(htmlContentFn) {
  jQuery('#modaldialog').remove()
  jQuery('body').prepend('<div id="modaldialog"><div></div></div>')
  htmlContentFn(jQuery('#modaldialog > div'))
}

function hideModalDialog() {
  jQuery('#modaldialog').remove()
}

function showWizard(htmlContentFns, index) {
  if (!index) {
    index = 0
  }
  if (index < htmlContentFns.length) {
    showModalDialog(function (index, elem) {
      var onComplete = function () {
        console.log(elem.find('.loading'))
        elem.find('.loading').remove()
        var buttonBar = jQuery('<div></div>')
        var cancelButton = jQuery('<input type="button" value="Abbrechen" class="button"/>')
        cancelButton.on('click', hideModalDialog)
        buttonBar.append(cancelButton)
        var submitButton = jQuery('<input type="submit" value="Weiter" style="margin-left: 15px;" class="button button-primary"/>')
        submitButton.on('click', showWizard.bind(this, htmlContentFns, index + 1))
        buttonBar.append(submitButton)
        elem.append(buttonBar)
      }.bind(this, elem)
      var res = htmlContentFns[index](elem, onComplete)
      if (res === undefined) {
        onComplete()
      } else {
        elem.append('<div class="loading">Lädt...</div>')
      }
    }.bind(this, index || 0))
  } else {
    hideModalDialog()
  }
}

function configureTelegram(botUserame) {
  showWizard([
    function (elem) {
      var siz = Math.min(elem.outerWidth() / 2 - 10, elem.outerHeight() - 20)
      var qrcode = jQuery('<div style="float: right;"></div>')
      elem.append(qrcode)
      new QRCode(qrcode.get(0), {
        text: 'https://telegram.org/dl/',
        width: siz,
        height: siz,
      })
      elem.append(
        '<h1>Telegram auf deinem Smartphone installieren</h1>' +
        '<p>Scanne den QR-Code rechts, oder:</p>' +
        '<ol>' +
          '<li>Geh in den App Store (iOS) oder Play Store (Android)</li>' +
          '<li>Suche nach dem App &quot;Telegram&quot;</li>' +
          '<li>Installiere es</li>' +
        '</ol>'
      )
    },
    function (elem) {
      elem.append(
        '<h1>Telegram konfigurieren</h1>' +
        '<p>Folge den Anweisungen im App</p>'
      )
    },
    function (elem, onComplete) {
      jQuery.post(ajaxurl, {
        'action': 'get_telegram_pin',
      }, function (response) {
        var tmp = JSON.parse(response)
        var pin = tmp.pin
        var siz = Math.min(elem.outerWidth() / 2 - 10, elem.outerHeight() - 20)
        var qrcode = jQuery('<div style="float: right;"></div>')
        elem.append(qrcode)
        new QRCode(qrcode.get(0), {
          text: 'https://telegram.me/' + botUserame + '?start=' + pin,
          width: siz,
          height: siz,
        })
        elem.append(
          '<h1>OLZ Bot aktivieren</h1>' +
          '<p>Scanne den QR-Code rechts, oder:</p>' +
          '<ol>' +
            '<li>Öffne das Telegram App</li>' +
            '<li>Suche nach &quot;' + botUserame + '&quot;</li>' +
            '<li>Eröffne einen Chat mit dem OLZ Bot</li>' +
            '<li>Geh auf &quot;Start&quot;</li>' +
            '<li>Gib den folgenden PIN ein:<br /><span class="pin">' + pin + '</span></li>' +
          '</ol>'
        )
        onComplete()
      })
      return false
    },
    function (elem) {
      elem.append(
        '<h1>Gratulation!</h1>' +
        '<p>Jetzt kannst du mit dem OLZ Bot abmachen, wann er dich über was informieren soll, das bei der OLZ gerade passiert.</p>'
      )
    },
  ])
}
