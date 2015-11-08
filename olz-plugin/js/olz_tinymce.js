/* eslint no-console: "off"*/
/* eslint no-empty: "off" */
/* eslint object-shorthand: "off" */
/* global jQuery, tinymce, olz_texte, olz_organigramm */
/* exported  */

/**
  TinyMCE Placeholder v1.0 - December 2013
  (c) 2013 Federico Isacchi - http://www.isacchi.eu
  license: http://www.opensource.org/licenses/mit-license.php

A customizable placeholder plugin for TinyMCE 4.
It can be used to create image placeholders for tokens like [!token_name].

Into the configuration of TineMCE 'placeholder_tokens' should be declared as an
array of objects.
Each object can have the following properties:
  token  The token name.
  title  [optional] The text that will be used into the menus.
  image  [optional] The image url that will be used as placeholder.
*/

tinymce.PluginManager.add('olz_placeholder', function(editor, url) {
  var token_order = ['texte', 'organigramm', 'auto']
  var tokens = {
    'texte': {
      title: 'Text...',
      text: function (dict) {
        var htmlout = 'Text über: <select onchange="var data = JSON.parse(this.parentElement.getAttribute(&quot;placeholder_data&quot;)); data[&quot;texte-id&quot;]=this.value; this.parentElement.setAttribute(&quot;placeholder_data&quot;, JSON.stringify(data));"><option>Bitte wählen...</option>'
        for (var j = 0; j < olz_texte.length; j++) {
          htmlout += '<option value="' + olz_texte[j].id + '"' + (dict['texte-id'] == olz_texte[j].id ? ' selected' : '') + '>' + olz_texte[j].title + '</option>'
        }
        htmlout += '</select>'
        return htmlout
      },
    },
    'organigramm': {
      title: 'Verantwortlicher...',
      text: function (dict) {
        var olz_person_options = [
          { id: 1, title: 'Name' },
          { id: 2, title: 'Name mit E-Mail Link' },
          { id: 3, title: 'Visitenkarte' },
        ]
        var htmlout = 'Verantwortlicher für&nbsp;<select onchange="var data = JSON.parse(this.parentElement.getAttribute(&quot;placeholder_data&quot;)); data[&quot;organigramm-id&quot;]=this.value; this.parentElement.setAttribute(&quot;placeholder_data&quot;, JSON.stringify(data));"><option>Bitte wählen...</option>'
        for (var j = 0; j < olz_organigramm.length; j++) {
          htmlout += '<option value="' + olz_organigramm[j].id + '"' + (dict['organigramm-id'] == olz_organigramm[j].id ? ' selected' : '') + '>' + olz_organigramm[j].title + '</option>'
        }
        htmlout += '</select>&nbsp;als&nbsp;<select onchange="var data = JSON.parse(this.parentElement.getAttribute(&quot;placeholder_data&quot;)); data[&quot;contact-type&quot;]=this.value; this.parentElement.setAttribute(&quot;placeholder_data&quot;, JSON.stringify(data));"><option>Bitte wählen...</option>'
        for (var k = 0; k < olz_person_options.length; k++) {
          htmlout += '<option value="' + olz_person_options[k].id + '"' + (dict['contact-type'] == olz_person_options[k].id ? ' selected' : '') + '>' + olz_person_options[k].title + '</option>'
        }
        htmlout += '</select>&nbsp;'
        return htmlout
      },
      selection: olz_organigramm,
    },
    'auto': {
      title: 'Ausschnitte...',
      text: function (dict) {
        var olz_auto = [
          { id: 1, title: 'Nächste 3 Trainings' },
        ]
        var htmlout = 'Ausschnitt: <select onchange="var data = JSON.parse(this.parentElement.getAttribute(&quot;placeholder_data&quot;)); data[&quot;auto-id&quot;]=this.value; this.parentElement.setAttribute(&quot;placeholder_data&quot;, JSON.stringify(data));"><option>Bitte wählen...</option>'
        for (var j = 0; j < olz_auto.length; j++) {
          htmlout += '<option value="' + olz_auto[j].id + '"' + (dict['auto-id'] == olz_auto[j].id ? ' selected' : '') + '>' + olz_auto[j].title + '</option>'
        }
        htmlout += '</select>'
        return htmlout
      },
    },
  }

  /**
    WYSIWYG -> source
    Substitutes the placeholders with the tokens.
  */
  function _toSrc(s) {
    var re
    var re_ph_start = '\\<\\!\\-\\-\\ olz\\_placeholder\\ \\-\\-\\>'
    var re_ph_end = '\\<\\!\\-\\-\\ \\/olz\\_placeholder\\ \\-\\-\\>'

    //to prevent the text equal to a token to be treated as a token
    re = new RegExp('\\<\\<\\!\\"(((?!\\"\\!\\>\\>).)+)\\"\\!\\>\\>', 'gi')
    s = s.replace(re, '<<&#x21;"$1"&#x21;>>')

    //substitutes the placeholders with the tokens
    re = new RegExp('(\\<div\\>\\s*\\<\\/div\\>\\s*)?\\<div\\>\\s*' + re_ph_start + '(((?!' + re_ph_end + ').)*)placeholder\\_data\\=\\"([^\\"]*)\\"(((?!' + re_ph_end + ').)*)' + re_ph_end + '\\s*\\<\\/div\\>(\\s*\\<div\\>\\s*\\<\\/div\\>)?', 'gi')
    s = s.replace(re, '<<!"$4"!>>')
    return s
  }


  /**
    source -> WYSIWYG
    Substitutes the tokens with the placeholders.
  */
  function _fromSrc(s) {
    var re, m
    re = new RegExp('\\<\\<\\!\\"(((?!\\"\\!\\>\\>).)+)\\"\\!\\>\\>', 'i')
    m = re.exec(s)
    for (var i = 0; m && i < 100; i++) {
      try {
        var dict = JSON.parse(m[1].replace(/&quot;/gi, '"'))
        if (!dict['ident'] || !tokens[dict['ident']]) {
          console.warn('Illegal token was JSON, but had no valid "ident" key:', m[1].replace(/&quot;/gi, '"'))
          continue
        }
        s = s.replace(re, '<div></div><div><!-- olz_placeholder --><div class="placeholder" placeholder_data="' + m[1].replace(/"/gi, '&quot;') + '">' + tokens[dict['ident']].text(dict) + '</div><!-- /olz_placeholder --></div><div></div>')
        m = re.exec(s)
      } catch (err) {
        console.warn(err, m[1].replace(/&quot;/gi, '"'))
      }
    }
    return s
  }


  if (editor.settings.extended_valid_elements == undefined) {
    editor.settings.extended_valid_elements = 'div[class|placeholder_data]'
  } else {
    var i = editor.settings.extended_valid_elements.search(/div[\s]*\[[^[\]]+\]/i)
    if (i == -1) {
      editor.settings.extended_valid_elements += ',div[class|placeholder_data]'
    } else {
      var a = editor.settings.extended_valid_elements.substr(i)
      a = a.substr(a.indexOf('[') + 1)
      editor.settings.extended_valid_elements = editor.settings.extended_valid_elements.substring(0,i) + 'div[placeholder_data|' + a
    }
  }

  //prepare the menu list to be used in addButton() and addMenuItem()
  var placeholder_menu = []
  for (var j = 0; j < token_order.length; j++) {
    var key = token_order[j]
    var token = tokens[key]
    if (token.title == undefined) {
      tokens[key].title = key
    }
    placeholder_menu.push({ text: token.title, token: key })

    // to prevent that special chars into the tokens make mess
    tokens[key].escapedToken = key.replace(/[-[\]/{}()*+?.\\^$|]/g, '\\$&')

    // if an image was not set then set a default one
    if (token.image == undefined) {
      tokens[key].image = url + '/placeholder.gif'
    }
  }

  editor.addButton('olz_placeholder', {
    text: 'OLZ',
    type: 'listbox',
    values: placeholder_menu,
    onselect: function(e) {
      editor.insertContent('<<!"' + JSON.stringify({ ident: e.control.settings.token }).replace(/"/gi, '&quot;') + '"!>>')
    },
  })
  editor.addMenuItem('olz_placeholder', {
    text: 'OLZ',
    context: 'insert',
    menu: placeholder_menu,
    onselect: function(e) {
      editor.insertContent('<<!"' + JSON.stringify({ ident: e.control.settings.token }).replace(/"/gi, '&quot;') + '"!>>')
    },
  })

  // Make Placeholders non-weirdly-editable
  editor.on('PreInit', function() {
    editor.parser.addAttributeFilter('class', function(nodes) {
      function hasClass(node, checkClassName) {
        return (' ' + node.attr('class') + ' ').indexOf(checkClassName) !== -1
      }
      var nlen = nodes.length
      for (var i = 0; i < nlen; i++) {
        var node = nodes[i]
        if (hasClass(node, 'placeholder')) {
          node.attr('contenteditable', 'false')
        }
      }
    })
  })

  editor.on('beforeSetContent', function(e) {
    e.content = _fromSrc(e.content)
  })
  editor.on('postProcess', function(e) {
    e.content = _toSrc(e.content)
  })
})
