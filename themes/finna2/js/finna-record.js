/*global VuFind, finna, removeHashFromLocation, getNewRecordTab, ajaxLoadTab */
finna.record = (function finnaRecord() {
  var accordionTitleHeight = 64;

  function initDescription() {
    var description = $('#description_text');
    if (description.length) {
      var id = description.data('id');
      var url = VuFind.path + '/AJAX/JSON?method=getDescription&id=' + id;
      $.getJSON(url)
        .done(function onGetDescriptionDone(response) {
          if (response.data.html.length > 0) {
            description.html(VuFind.updateCspNonce(response.data.html));

            // Make sure any links open in a new window
            description.find('a').attr('target', '_blank');

            description.wrapInner('<div class="truncate-field wide"><p class="summary"></p></div>');
            finna.layout.initTruncate(description);
            if (!$('.hide-details-button').hasClass('hidden')) {
              $('.record-information .description').addClass('too-long');
              $('.record-information .description .more-link.wide').click();
            }
          } else {
            description.hide();
          }
        })
        .fail(function onGetDescriptionFail() {
          description.hide();
        });
    }
  }

  function initHideDetails() {
    $('.show-details-button').on('click', function onClickShowDetailsButton() {
      $('.record-information .record-details-more').removeClass('hidden');
      $(this).addClass('hidden');
      $('.hide-details-button').removeClass('hidden');
      $('.record .description .more-link.wide').click();
      sessionStorage.setItem('finna_record_details', '1');
    });
    $('.hide-details-button').click (function onClickHideDetailsButton() {
      $('.record-information .record-details-more').addClass('hidden');
      $(this).addClass('hidden');
      $('.show-details-button').removeClass('hidden');
      $('.record .description .less-link.wide').click();
      sessionStorage.removeItem('finna_record_details');
    });
    if ($('.record-information').height() > 350 && $('.show-details-button')[0]) {
      $('.record-information .description').addClass('too-long');
      if (sessionStorage.getItem('finna_record_details')) {
        $('.show-details-button').click();
      } else {
        $('.hide-details-button').click();
      }
    }
  }

  function getRequestLinkData(element, recordId) {
    var vars = {}, hash;
    var hashes = element.href.slice(element.href.indexOf('?') + 1).split('&');

    for (var i = 0; i < hashes.length; i++) {
      hash = hashes[i].split('=');
      var x = hash[0];
      var y = hash[1];
      vars[x] = y;
    }
    vars.id = recordId;
    return vars;
  }

  function checkRequestsAreValid(elements, requestType) {
    if (!elements[0]) {
      return;
    }
    var recordId = elements[0].href.match(/\/Record\/([^/]+)\//)[1];

    var vars = [];
    $.each(elements, function handleElement(idx, element) {
      vars.push(getRequestLinkData(element, recordId));
    });

    var url = VuFind.path + '/AJAX/JSON?method=checkRequestsAreValid';
    $.ajax({
      dataType: 'json',
      data: {id: recordId, requestType: requestType, data: vars},
      method: 'POST',
      cache: false,
      url: url
    })
      .done(function onCheckRequestDone(responses) {
        $.each(responses.data, function handleResponse(idx, response) {
          var element = elements[idx];
          if (response.status) {
            $(element).removeClass('disabled')
              .html(VuFind.updateCspNonce(response.msg));
          } else {
            $(element).remove();
          }
        });
      });
  }

  function fetchHoldingsDetails(elements) {
    if (!elements[0]) {
      return;
    }

    $.each(elements, function handleElement(idx, element) {
      $(element).removeClass('hidden');
      var url = VuFind.path + '/AJAX/JSON?method=getHoldingsDetails';
      $.ajax({
        dataType: 'json',
        data: $(element).data(),
        method: 'POST',
        cache: false,
        url: url
      })
        .done(function onGetDetailsDone(response) {
          $(element).addClass('hidden');
          var $group = $(element).parents('.holdings-group');
          $group.find('.load-more-indicator-ajax').addClass('hidden');
          // This can be called several times to load more items. Only update the hidden element.
          $group.find('.holdings-details-ajax.hidden').html(VuFind.updateCspNonce(response.data.details)).removeClass('hidden');
          var $itemsContainer = $group.find('.holdings-items-ajax.hidden');
          $itemsContainer.html(VuFind.updateCspNonce(response.data.items)).removeClass('hidden');
          checkRequestsAreValid($group.find('.expandedCheckRequest').removeClass('expandedCheckRequest'), 'Hold');
          checkRequestsAreValid($group.find('.expandedCheckStorageRetrievalRequest').removeClass('expandedCheckStorageRetrievalRequest'), 'StorageRetrievalRequest');
          checkRequestsAreValid($group.find('.expandedCheckILLRequest').removeClass('expandedCheckILLRequest'), 'ILLRequest');
          VuFind.lightbox.bind($itemsContainer);
          $group.find('.load-more-items-ajax').on('click', function loadMore() {
            var $elem = $(this);
            $elem.addClass('hidden');
            $elem.siblings('.load-more-indicator-ajax').removeClass('hidden');
            fetchHoldingsDetails($elem.parent());
          });
        })
        .fail(function onGetDetailsFail() {
          $(element).text(VuFind.translate('error_occurred'));
        });
    });
  }

  function setUpCheckRequest() {
    checkRequestsAreValid($('.expandedCheckRequest').removeClass('expandedCheckRequest'), 'Hold');
    checkRequestsAreValid($('.expandedCheckStorageRetrievalRequest').removeClass('expandedCheckStorageRetrievalRequest'), 'StorageRetrievalRequest');
    checkRequestsAreValid($('.expandedCheckILLRequest').removeClass('expandedCheckILLRequest'), 'ILLRequest');
    fetchHoldingsDetails($('.expandedGetDetails').removeClass('expandedGetDetails'));
  }

  function initHoldingsControls() {
    $('.holdings-container-heading').on('click', function onClickHeading(e) {
      if ($(e.target).hasClass('location-service') || $(e.target).parents().hasClass('location-service')) {
        return;
      }
      $(this).nextUntil('.holdings-container-heading').toggleClass('collapsed');
      if ($('.location .fa', this).hasClass('fa-arrow-down')) {
        $('.location .fa', this).removeClass('fa-arrow-down');
        $('.location .fa', this).addClass('fa-arrow-right');
      }
      else {
        $('.location .fa', this).removeClass('fa-arrow-right');
        $('.location .fa', this).addClass('fa-arrow-down');
        var rows = $(this).nextUntil('.holdings-container-heading');
        checkRequestsAreValid(rows.find('.collapsedCheckRequest').removeClass('collapsedCheckRequest'), 'Hold', 'holdBlocked');
        checkRequestsAreValid(rows.find('.collapsedCheckStorageRetrievalRequest').removeClass('collapsedCheckStorageRetrievalRequest'), 'StorageRetrievalRequest', 'StorageRetrievalRequestBlocked');
        checkRequestsAreValid(rows.find('.collapsedCheckILLRequest').removeClass('collapsedCheckILLRequest'), 'ILLRequest', 'ILLRequestBlocked');
        fetchHoldingsDetails(rows.filter('.collapsedGetDetails').removeClass('collapsedGetDetails'));
      }
    });
  }

  function augmentOnlineLinksFromHoldings() {
    $('.electronic-holdings a').each(function handleLink() {
      var $a = $(this);
      var href = $a.attr('href');
      var $recordUrls = $('.recordURLs');
      var $existing = $recordUrls.find('a[href="' + href + '"]');
      var desc = $a.text();
      if ($existing.length === 0 || $existing.text() !== desc) {
        // No existing link, prepend to the list
        var newLink = $('.recordURLs .url-template').html();
        newLink = newLink
          .replace('HREF', href)
          .replace('DESC', desc)
          .replace('SOURCE', $('.record-holdings-table:not(.electronic-holdings) .holdings-title').text());

        var $newLink = $(newLink)
          .removeClass('url-template')
          .removeClass('hidden');

        if ($existing.length === 0) {
          $newLink.prependTo($recordUrls);
        } else {
          $existing.replaceWith($newLink);
        }
      }
    });

  }

  function setupHoldingsTab() {
    initHoldingsControls();
    setUpCheckRequest();
    augmentOnlineLinksFromHoldings();
    finna.layout.initLocationService();
    finna.layout.initJumpMenus($('.holdings-tab'));
    VuFind.lightbox.bind($('.holdings-tab'));
  }

  function setupLocationsEad3Tab() {
    $('.holdings-container-heading').on('click', function onClickHeading() {
      $(this).nextUntil('.holdings-container-heading').toggleClass('collapsed');
      if ($('.location .fa', this).hasClass('fa-arrow-down')) {
        $('.location .fa', this).removeClass('fa-arrow-down');
        $('.location .fa', this).addClass('fa-arrow-right');
      }
      else {
        $('.location .fa', this).removeClass('fa-arrow-right');
        $('.location .fa', this).addClass('fa-arrow-down');
      }
    });
  }

  function initRecordNaviHashUpdate() {
    $(window).on('hashchange', function onHashChange() {
      $('.pager a').each(function updateHash(i, a) {
        a.hash = window.location.hash;
      });
    });
    $(window).trigger('hashchange');
  }

  function initAudioAccordion() {
    $('.audio-accordion .audio-item-wrapper').first().addClass('active');
    $('.audio-accordion .audio-title-wrapper').on('click', function audioAccordionClicker() {
      $('.audio-accordion .audio-item-wrapper.active').removeClass('active');
      $(this).parent().addClass('active');
    });
  }

  // The accordion has a delicate relationship with the tabs. Handle with care!
  function _toggleAccordion(accordion, _initialLoad) {
    var initialLoad = typeof _initialLoad === 'undefined' ? false : _initialLoad;
    var tabid = accordion.find('.accordion-toggle a').data('tab');
    var $recordTabs = $('.record-tabs');
    var $tabContent = $recordTabs.find('.tab-content');
    if (initialLoad || !accordion.hasClass('active')) {
      // Move tab content under the correct accordion toggle
      $tabContent.insertAfter(accordion);
      if (accordion.hasClass('noajax') && !$recordTabs.find('.' + tabid + '-tab').length) {
        return true;
      }
      $('.record-accordions').find('.accordion.active').removeClass('active');
      accordion.addClass('active');
      $recordTabs.find('.tab-pane.active').removeClass('active');
      if (!initialLoad && $('.record-accordions').is(':visible')) {
        $('html, body').animate({scrollTop: accordion.offset().top - accordionTitleHeight}, 150);
      }

      if ($recordTabs.find('.' + tabid + '-tab').length > 0) {
        $recordTabs.find('.' + tabid + '-tab').addClass('active');
        if (accordion.hasClass('initiallyActive')) {
          removeHashFromLocation();
        } else {
          window.location.hash = tabid;
        }
        return false;
      } else {
        var newTab = getNewRecordTab(tabid).addClass('active');
        $recordTabs.find('.tab-content').append(newTab);
        return ajaxLoadTab(newTab, tabid, !$(this).parent().hasClass('initiallyActive'));
      }
    }
    return false;
  }

  function initRecordAccordion() {
    $('.record-accordions .accordion-toggle').on('click', function accordionClicked(e) {
      return _toggleAccordion($(e.target).closest('.accordion'));
    });
    if ($('.mobile-toolbar').length > 0 && $('.accordion-holdings').length > 0) {
      $('.mobile-toolbar .library-link li').removeClass('hidden');
      $('.mobile-toolbar .library-link li').on('click', function onLinkClick(e) {
        e.stopPropagation();
        $('html, body').animate({scrollTop: $('#tabnav').offset().top - accordionTitleHeight}, 150);
        _toggleAccordion($('.accordion-holdings'));
      });
    }
  }

  function applyRecordAccordionHash(callback) {
    var newTab = typeof window.location.hash !== 'undefined'
      ? window.location.hash.toLowerCase() : '';

    // Open tab in url hash
    var $tab = $("a:not(.feed-tab-anchor,.feed-accordion-anchor)[data-tab='" + newTab.substr(1) + "']");
    var accordion = (newTab.length <= 1 || newTab === '#tabnav' || $tab.length === 0)
      ? $('.record-accordions .accordion.initiallyActive')
      : $tab.closest('.accordion');
    if (accordion.length > 0) {
      //onhashchange is an object, so we avoid that later
      if (typeof callback === 'function') {
        callback(accordion);
      } else {
        var mobile = $('.mobile-toolbar');
        var initialLoad = mobile.length > 0 ? !mobile.is(':visible') : true;
        _toggleAccordion(accordion, initialLoad);
      }
    }
  }

  //Toggle accordion at the start so the accordions work properly
  function initialToggle(accordion) {
    var $recordTabs = $('.record-tabs');
    var $tabContent = $recordTabs.find('.tab-content');
    var tabid = accordion.find('.accordion-toggle a').data('tab');
    $tabContent.insertAfter(accordion);
    if (accordion.hasClass('noajax') && !$recordTabs.find('.' + tabid + '-tab').length) {
      return true;
    }

    $('.record-accordions').find('.accordion.active').removeClass('active');
    accordion.addClass('active');
    $recordTabs.find('.tab-pane.active').removeClass('active');
    if ($recordTabs.find('.' + tabid + '-tab').length > 0) {
      $recordTabs.find('.' + tabid + '-tab').addClass('active');
      if (accordion.hasClass('initiallyActive')) {
        removeHashFromLocation();
      }
    }
  }

  function loadRecommendedRecords(container, method)
  {
    if (container.length === 0) {
      return;
    }
    var spinner = container.find('.fa-spinner').removeClass('hide');
    var data = {
      method: method,
      id: container.data('id')
    };
    if ('undefined' !== typeof container.data('source')) {
      data.source = container.data('source');
    }
    $.getJSON(VuFind.path + '/AJAX/JSON', data)
      .done(function onGetRecordsDone(response) {
        if (response.data.html.length > 0) {
          container.html(VuFind.updateCspNonce(response.data.html));
        }
        spinner.addClass('hidden');
      })
      .fail(function onGetRecordsFail() {
        spinner.addClass('hidden');
        container.text(VuFind.translate('error_occurred'));
      });
  }

  function loadSimilarRecords()
  {
    loadRecommendedRecords($('.sidebar .similar-records'), 'getSimilarRecords');
  }

  function loadRecordDriverRelatedRecords()
  {
    loadRecommendedRecords($('.sidebar .record-driver-related-records'), 'getRecordDriverRelatedRecords');
  }

  function initRecordVersions(_holder) {
    VuFind.recordVersions.init(_holder);
  }

  function handleRedirect(oldId, newId) {
    if (window.history.replaceState) {
      var pathParts = window.location.pathname.split('/');
      pathParts.forEach(function handlePathPart(part, i) {
        if (decodeURIComponent(part) === oldId) {
          pathParts[i] = encodeURIComponent(newId);
        }
      });
      window.history.replaceState(null, document.title, pathParts.join('/') + window.location.search + window.location.hash);
    }
  }

  function init() {
    initHideDetails();
    initDescription();
    initRecordNaviHashUpdate();
    initRecordAccordion();
    initAudioAccordion();
    applyRecordAccordionHash(initialToggle);
    $(window).on('hashchange', applyRecordAccordionHash);
    loadSimilarRecords();
    loadRecordDriverRelatedRecords();
    finna.authority.initAuthorityResultInfo();
  }

  var my = {
    checkRequestsAreValid: checkRequestsAreValid,
    init: init,
    setupHoldingsTab: setupHoldingsTab,
    setupLocationsEad3Tab: setupLocationsEad3Tab,
    initRecordVersions: initRecordVersions,
    handleRedirect: handleRedirect
  };

  return my;
})();
