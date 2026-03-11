(function (Drupal, once) {
  'use strict';

  var PAGE_SIZE_OPTIONS = [10, 25, 50, 100];

  Drupal.behaviors.dhcrAdminTableSearch = {
    attach: function attach(context) {
      once('dhcr-admin-table-search', '.dhcr-searchable-table', context).forEach(function (root) {
        var input = root.querySelector('[data-dhcr-table-search-input]');
        var table = root.querySelector('[data-dhcr-table-search-table]');
        var tbody = table ? table.querySelector('tbody') : null;

        if (!table || !tbody) {
          return;
        }

        var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr')).filter(function (row) {
          return !row.classList.contains('dhcr-table-search__empty-row');
        });
        var pageSize = getPageSize(root);
        var currentPage = 1;
        var controls = createControls(root, table, pageSize);

        if (input) {
          input.addEventListener('input', function () {
            currentPage = 1;
            update();
          });
        }

        controls.forEach(function (control) {
          control.prevButton.addEventListener('click', function () {
            currentPage -= 1;
            update();
          });

          control.nextButton.addEventListener('click', function () {
            currentPage += 1;
            update();
          });

          control.pageSizeSelect.addEventListener('change', function () {
            var value = parseInt(control.pageSizeSelect.value || '', 10);
            if (!Number.isFinite(value) || PAGE_SIZE_OPTIONS.indexOf(value) === -1) {
              value = 25;
            }
            pageSize = value;
            currentPage = 1;
            syncPageSize(controls, value);
            update();
          });
        });

        update();

        function update() {
          var needle = input ? input.value.trim().toLowerCase() : '';
          var filteredRows = rows.filter(function (row) {
            var text = (row.getAttribute('data-dhcr-search-text') || row.textContent || '').toLowerCase();
            return needle === '' || text.indexOf(needle) !== -1;
          });
          var filteredCount = filteredRows.length;
          var pageCount = Math.max(1, Math.ceil(filteredCount / pageSize));

          if (currentPage < 1) {
            currentPage = 1;
          }
          if (currentPage > pageCount) {
            currentPage = pageCount;
          }

          rows.forEach(function (row) {
            row.hidden = true;
          });

          if (filteredCount > 0) {
            var start = (currentPage - 1) * pageSize;
            var end = start + pageSize;
            filteredRows.slice(start, end).forEach(function (row) {
              row.hidden = false;
            });
          }

          toggleEmptyRow(tbody, table, filteredCount === 0);
          updateControls(controls, rows.length, filteredCount, currentPage, pageCount, pageSize);
        }
      });
    }
  };

  function toggleEmptyRow(tbody, table, show) {
    var existing = tbody.querySelector('.dhcr-table-search__empty-row');
    if (!show) {
      if (existing) {
        existing.remove();
      }
      return;
    }

    if (existing) {
      return;
    }

    var tr = document.createElement('tr');
    tr.className = 'dhcr-table-search__empty-row';
    var td = document.createElement('td');
    td.colSpan = table.querySelectorAll('thead th').length || 1;
    td.textContent = Drupal.t('No matching rows.');
    tr.appendChild(td);
    tbody.appendChild(tr);
  }

  function getPageSize(root) {
    var value = parseInt(root.getAttribute('data-dhcr-page-size') || '', 10);
    if (Number.isFinite(value) && value > 0) {
      return value;
    }
    return 25;
  }

  function createControls(root, table, initialPageSize) {
    var top = buildPager('top', initialPageSize);
    var bottom = buildPager('bottom', initialPageSize);
    root.insertBefore(top.wrap, table);
    table.insertAdjacentElement('afterend', bottom.wrap);
    return [top, bottom];
  }

  function buildPager(position, initialPageSize) {
    var wrap = document.createElement('div');
    wrap.className = 'dhcr-table-pager dhcr-table-pager--' + position;

    var left = document.createElement('div');
    left.className = 'dhcr-table-pager__left';

    var countText = document.createElement('div');
    countText.className = 'dhcr-table-pager__count';
    countText.setAttribute('data-dhcr-count-text', '1');
    left.appendChild(countText);

    var pageSizeWrap = document.createElement('label');
    pageSizeWrap.className = 'dhcr-table-pager__pagesize';
    pageSizeWrap.textContent = Drupal.t('Rows per page');

    var pageSizeSelect = document.createElement('select');
    pageSizeSelect.className = 'dhcr-table-pager__select';
    pageSizeSelect.setAttribute('aria-label', Drupal.t('Rows per page'));

    PAGE_SIZE_OPTIONS.forEach(function (size) {
      var option = document.createElement('option');
      option.value = String(size);
      option.textContent = String(size);
      if (size === initialPageSize) {
        option.selected = true;
      }
      pageSizeSelect.appendChild(option);
    });

    pageSizeWrap.appendChild(pageSizeSelect);
    left.appendChild(pageSizeWrap);
    wrap.appendChild(left);

    var nav = document.createElement('div');
    nav.className = 'dhcr-table-pager__nav';

    var prevButton = document.createElement('button');
    prevButton.type = 'button';
    prevButton.className = 'dhcr-table-pager__button';
    prevButton.setAttribute('data-dhcr-prev', '1');
    prevButton.textContent = Drupal.t('Previous');
    nav.appendChild(prevButton);

    var nextButton = document.createElement('button');
    nextButton.type = 'button';
    nextButton.className = 'dhcr-table-pager__button';
    nextButton.setAttribute('data-dhcr-next', '1');
    nextButton.textContent = Drupal.t('Next');
    nav.appendChild(nextButton);

    wrap.appendChild(nav);

    return {
      wrap: wrap,
      countText: countText,
      pageSizeSelect: pageSizeSelect,
      prevButton: prevButton,
      nextButton: nextButton
    };
  }

  function syncPageSize(controlList, pageSize) {
    controlList.forEach(function (control) {
      control.pageSizeSelect.value = String(pageSize);
    });
  }

  function updateControls(controlList, totalCount, filteredCount, page, pageCount, pageSize) {
    var text = Drupal.t('This table has @count items.', { '@count': filteredCount });
    if (filteredCount !== totalCount) {
      text += ' ' + Drupal.t('(@filtered of @total shown)', {
        '@filtered': filteredCount,
        '@total': totalCount
      });
    }

    text += ' ' + Drupal.t('Page @page of @pages', {
      '@page': page,
      '@pages': pageCount
    });

    text += ' ' + Drupal.t('(Rows per page: @size)', { '@size': pageSize });

    controlList.forEach(function (control) {
      control.countText.textContent = text;
      control.prevButton.disabled = filteredCount === 0 || page <= 1;
      control.nextButton.disabled = filteredCount === 0 || page >= pageCount;
    });
  }
})(Drupal, once);
