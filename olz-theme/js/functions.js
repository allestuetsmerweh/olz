/* eslint no-console: "off"*/
/* eslint no-empty: "off" */
/* global jQuery, mapboxgl, ajaxurl, handleOLZFormResponse, basicMenu, monthNames */
/* exported buildArchive, map, toggleMap, toggleUCForm, submitOLZForm, MailTo */

/*
Theme Name: OLZ
Description: The OLZ JavaScript functions file
*/


jQuery().ready(function () {
  // Menu Scroll
  jQuery(window).on('scroll', menuOnScroll)

  jQuery('#menu-bar').on('click', function () {
    if (jQuery('#menu-bar').css('cursor') == 'pointer') {
      scrollToMenu(500)
    }
  })

  // Build Menu
  var menuHTML = basicMenu.map(function (elem, ind) {
    if ('text' in elem) {
      var href = 'href' in elem ? ' href="' + elem.href + '"' : ''
      var additionalClass = 'class' in elem ? ' ' + elem['class'] : ''
      return '<a' + href + ' class="menu-point' + additionalClass + '" id="menu-ind-' + ind + '">' + elem.text + '</a>'
    } else {
      return '<div class="menu-separator" id="menu-ind-' + ind + '"></div>'
    }
  }).join('\n')
  jQuery('#menu-list-above').html(menuHTML)
  jQuery('#menu-list-below').html(menuHTML)

  // Menu Button (Hamburger)
  jQuery('#menu-button').on('click', function () {
    if (jQuery('#menu-list').is(':visible')) {
      jQuery('#menu-list').css('display', '')
    } else {
      jQuery('#menu-list').css('display', 'block')
    }
  })

  // Responsive Window Resize
  jQuery(window).on('resize', function () {
    menuOnScroll()
  })

  // Swipebox Init
  jQuery('.swipebox').swipebox()

  // Scroll Init
  console.log(jQuery(window).scrollTop())
  if (jQuery(window).scrollTop() == 0) {
    setTimeout(function () {
      scrollToMenu(1)
    }, 1)
  } else {
    menuOnScroll()
  }
})

function menuOnScroll() {

}

function scrollToMenu(duration) {
  var placeholder = jQuery('#menu-placeholder')
  jQuery('html, body').animate({
    scrollTop: placeholder.offset().top - (window.innerHeight - placeholder.outerHeight()) / 2 + 1,
  }, duration)
  setTimeout(menuOnScroll, duration)
}

function MailTo() {

}


function buildArchive(ptident, dateTimeStr, archiveData) {
  var dateTime = new Date(Date.parse(dateTimeStr))
  var year = dateTime.getFullYear()
  var month = dateTime.getMonth()

  var timelineAbove = jQuery('<div></div>')
  var yearAbove = year
  var addYearsAbove = function (numYears) {
    for (var y = 0; y < numYears; y++) {
      var accordionTitle = jQuery('<div class="accordion-header"><div>' + yearAbove + '</div></div>')
      var accordionContent = jQuery('<div class="accordion-content"></div>')
      accordionTitle.on('click', function (accordionContent) {
        accordionToggle(accordionContent)
      }.bind(this, accordionContent))
      for (var m = yearAbove == year ? month : 0; m < 12; m++) {
        var link = jQuery('<div class="link">' + monthNames[m] + ' ' + yearAbove + '</div>')
        link.on('click', function (year, month) {
          var date = new Date(Date.UTC(year, month, 15))
          if (date < new Date()) {
            return scrollToMenu(500)
          }
          jQuery.post(ajaxurl, {
            'action': 'get_archive_page',
            'post_type': ptident,
            'date': date.toJSON(),
          }, function (response) {
            var page = JSON.parse(response)
            var needsLoading = page > archiveData.contentAbove.length
            if (needsLoading) {
              for (var i = archiveData.contentAbove.length; i <= page; i++) {
                archiveData.contentAbove.push(null)
              }
              redraw()
            }
            var floorElem = jQuery('.page-' + Math.floor(page) + '-above')
            var ceilElem = jQuery('.page-' + Math.ceil(page) + '-above')
            var part = page - Math.floor(page)
            var scrollTop = (floorElem.offset().top * (1 - part) + ceilElem.offset().top * part)
            jQuery('html, body').animate({
              scrollTop: scrollTop - window.innerHeight / 2,
            }, 500)
          })
        }.bind(this, yearAbove, m))
        accordionContent.prepend(link)
      }
      timelineAbove.prepend(accordionTitle)
      timelineAbove.prepend(accordionContent)
      yearAbove++
    }
  }
  addYearsAbove(5)
  var moreTimelineAbove = jQuery('<div class="accordion-header"><div>spätere...</div></div>')
  moreTimelineAbove.on('click', function () {
    addYearsAbove(5)
  })
  jQuery('.timeline.above').html('').prepend(timelineAbove).prepend(moreTimelineAbove)

  var timelineBelow = jQuery('<div></div>')
  var yearBelow = year
  var addYearsBelow = function (numYears) {
    for (var y = 0; y < numYears; y++) {
      var accordionTitle = jQuery('<div class="accordion-header"><div>' + yearBelow + '</div></div>')
      var accordionContent = jQuery('<div class="accordion-content"></div>')
      accordionTitle.on('click', function (accordionContent) {
        accordionToggle(accordionContent)
      }.bind(this, accordionContent))
      for (var m = yearBelow == year ? month : 11; m >= 0; m--) {
        var link = jQuery('<div class="link">' + monthNames[m] + ' ' + yearBelow + '</div>')
        link.on('click', function (year, month) {
          var date = new Date(Date.UTC(year, month, 15))
          if (date > new Date()) {
            alert(0)
            return
          }
          jQuery.post(ajaxurl, {
            'action': 'get_archive_page',
            'post_type': ptident,
            'date': date.toJSON(),
          }, function (response) {
            var page = JSON.parse(response)
            var needsLoading = page > archiveData.contentBelow.length
            if (needsLoading) {
              for (var i = archiveData.contentBelow.length; i <= page; i++) {
                archiveData.contentBelow.push(null)
              }
              redraw()
            }
            var floorElem = jQuery('.page-' + Math.floor(page) + '-below')
            var ceilElem = jQuery('.page-' + Math.ceil(page) + '-below')
            var part = page - Math.floor(page)
            var scrollTop = (floorElem.offset().top * (1 - part) + ceilElem.offset().top * part)
            jQuery('html, body').animate({
              scrollTop: scrollTop - window.innerHeight / 2,
            }, 500)
          })
        }.bind(this, yearBelow, m))
        accordionContent.append(link)
      }
      timelineBelow.append(accordionTitle)
      timelineBelow.append(accordionContent)
      yearBelow--
    }
  }
  addYearsBelow(5)
  var moreTimelineBelow = jQuery('<div class="accordion-header"><div>ältere...</div></div>')
  moreTimelineBelow.on('click', function () {
    addYearsBelow(5)
  })
  jQuery('.timeline.below').html('').append(timelineBelow).append(moreTimelineBelow)

  var redraw = function () {
    var htmlAbove = ''
    var stillHasContentAbove = true
    archiveData.contentAbove.map(function (aboveContent, index) {
      stillHasContentAbove = (aboveContent !== '')
      if (aboveContent === null) {
        htmlAbove = '<div class="placeholder page-' + (index + 1) + '-above">Mehr...</div>' + htmlAbove
      } else {
        htmlAbove = '<div class="position-mark page-' + (index + 1) + '-above"></div>' + aboveContent + htmlAbove
      }
    })
    if (stillHasContentAbove) {
      htmlAbove = '<div class="placeholder page-' + (archiveData.contentAbove.length + 1) + '-above">Mehr...</div>' + htmlAbove
    }
    htmlAbove = htmlAbove + '<div class="position-mark page-0-above"></div>'
    jQuery('.articlelist.above').html(htmlAbove)

    var htmlBelow = ''
    var stillHasContentBelow = true
    archiveData.contentBelow.map(function (belowContent, index) {
      stillHasContentBelow = (belowContent !== '')
      if (belowContent === null) {
        htmlBelow = htmlBelow + '<div class="placeholder page-' + (index + 1) + '-below">Mehr...</div>'
      } else {
        htmlBelow = htmlBelow + belowContent + '<div class="position-mark page-' + (index + 1) + '-below"></div>'
      }
    })
    if (stillHasContentBelow) {
      htmlBelow = htmlBelow + '<div class="placeholder page-' + (archiveData.contentBelow.length + 1) + '-below">Mehr...</div>'
    }
    htmlBelow = '<div class="position-mark page-0-below"></div>' + htmlBelow
    jQuery('.articlelist.below').html(htmlBelow)
  }
  redraw()
  archiveData.loading = {}
  archiveData.checkTimeout = false
  archiveData.scrolledDuringCheckTimeout = false
  var checkLoad = function () {
    if (!archiveData.checkTimeout) {
      archiveData.checkTimeout = setTimeout(doCheckLoad, 300)
      archiveData.scrolledDuringCheckTimeout = false
    } else {
      archiveData.scrolledDuringCheckTimeout = true
    }
  }
  var doCheckLoad = function () {
    archiveData.checkTimeout = false
    if (archiveData.scrolledDuringCheckTimeout) {
      archiveData.checkTimeout = setTimeout(doCheckLoad, 300)
      archiveData.scrolledDuringCheckTimeout = false
    }
    var thresh = 500
    jQuery('.articlelist > .placeholder').map(function (index, elem) {
      var ref = /page-([0-9]+)-(above|below)/.exec(elem.getAttribute('class'))
      if (archiveData.loading[ref[0]]) {
        return
      }
      var rect = elem.getBoundingClientRect()
      if (
        (rect.bottom < window.innerHeight + thresh && rect.bottom > -thresh) ||
        (rect.top < window.innerHeight + thresh && rect.top > -thresh)
      ) {
        archiveData.loading[ref[0]] = true
        jQuery.post(ajaxurl, {
          'action': 'get_archive_chunk',
          'post_type': ptident,
          'page': (ref[2] === 'above' ? -1 : 1) * ref[1],
        }, function (ref, elem, response) {
          var newElems = JSON.parse(response)
          if (ref[2] === 'above') {
            archiveData.contentAbove[ref[1] - 1] = newElems
          } else {
            archiveData.contentBelow[ref[1] - 1] = newElems
          }
          delete archiveData.loading[ref[0]]
          var heiBefore = jQuery(document).height()
          var rect = elem.getBoundingClientRect()
          redraw()
          if ((rect.top + rect.bottom) / 2 < window.innerHeight / 2) {
            var heiAfter = jQuery(document).height()
            var oldScrollTop = jQuery(window).scrollTop()
            jQuery(window).scrollTop(oldScrollTop + heiAfter - heiBefore)
          }
        }.bind(this, ref, elem))
      }
    })
  }
  jQuery(window).on('scroll', checkLoad)
}

function map(lat, lng, elem) {
  // Neue Mapbox Karte
  // Link (im Moment wird noch auf Search.ch verlinkt, denn dort sieht man öV Haltestellen): https://api.tiles.mapbox.com/v4/allestuetsmerweh.m35pe3he/page.html?access_token=pk.eyJ1IjoiYWxsZXN0dWV0c21lcndlaCIsImEiOiJHbG9tTzYwIn0.kaEGNBd9zMvc0XkzP70r8Q#15/"+lat+"/"+lng+"
  // <a href='http://map.search.ch/" + xkoord + "," + ykoord + "' target='_blank'>

  var mapboxglTag = document.createElement('script')
  mapboxglTag.onload = function () {
    mapboxgl.accessToken = 'pk.eyJ1Ijoib2x6aW1tZXJlYmVyZyIsImEiOiJjajl3cHNyMDI3ZGp6MnhxeW1qZXVpdnk4In0.VcKf-JDSK6ltrowMopc-pQ'
    var map = new mapboxgl.Map({
      center: [lng, lat],
      container: elem.id,
      style: 'mapbox://styles/mapbox/outdoors-v10',
      scrollZoom: false,
      zoom: 12,
    })
    map.addControl(new mapboxgl.NavigationControl())
    new mapboxgl.Marker().setLngLat([lng, lat]).addTo(map)
  }
  mapboxglTag.src = 'https://api.tiles.mapbox.com/mapbox-gl-js/v0.41.0/mapbox-gl.js'
  document.body.appendChild(mapboxglTag)
  jQuery(document.body).append('<link href="https://api.tiles.mapbox.com/mapbox-gl-js/v0.41.0/mapbox-gl.css" rel="stylesheet" />')
}

function toggleMap(elemid, lat, lng) {
  var div = document.getElementById(elemid)
  var img = document.getElementById(elemid + '_img')
  if (img) {
    div.innerHTML = '<a href="" onclick="toggleMap(&quot;' + elemid + '&quot;,' + lat + ',' + lng + ');return false;" class="linkmap">Karte zeigen</a >'
  } else {
    var wid = div.offsetWidth - 20
    div.innerHTML = '<a href="" onclick="toggleMap(&quot;' + elemid + '&quot;,' + lat + ',' + lng + ');return false;" class="linkmap">Karte ausblenden</a><br>' + map(lat, lng, wid, 300, elemid + '_img')
  }
  return false
}

function toggleUCForm(ptident) {
  var div = jQuery('#uc_form_' + ptident)
  if (div.is(':hidden')) {
    div.slideDown()
  } else {
    div.slideUp()
  }
  return false
}

function submitOLZForm(ident, action, args, idents) {
  var data = {}
  for (var i = 0; i < idents.length; i++) {
    data[idents[i]] = jQuery('#olz_form_' + idents[i]).val()
  }
  args['action'] = action
  args['data'] = data
  jQuery('#olz_form_' + ident + '_response').html('Bitte warten...')
  jQuery.post(ajaxurl, args, function (response) {
    var tmp = JSON.parse(response)
    var html = '<div class="response-' + tmp[0] + '">' + tmp[1] + '</div>'
    jQuery('#olz_form_' + ident + '_response').html(html)
    if (!tmp[2]) {
      tmp[2] = {}
    }
    try {
      handleOLZFormResponse[ident](ident, tmp[2])
    } catch (err) {
      console.warn('handleOLZFormResponse[' + JSON.stringify(ident) + '] failed:', err)
    }
  })
}

function accordionToggle(contentElem) {
  if (contentElem.is(':visible')) {
    contentElem.slideUp()
    //jQuery('#accordion-header-' + year).removeClass('active-header')
  } else {
    contentElem.slideDown()
    //jQuery('#accordion-header-' + year).addClass('active-header')
  }
}
