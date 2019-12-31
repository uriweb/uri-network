(function(){
    'use strict';

    window.addEventListener( 'load', initSort, false );

    function initSort() {
        document.querySelectorAll('table').forEach(table => table.className += ' sortable');
        document.querySelectorAll('thead th').forEach(th => th.addEventListener('click', (() => {
            const tb = th.closest('table').querySelector('tbody');
            Array.from(tb.querySelectorAll('tr'))
                .sort(comparer(Array.from(th.parentNode.children).indexOf(th), this.asc = !this.asc))
                .forEach(tr => tb.appendChild(tr) );
        })));
    }

    const getCellValue = (tr, idx) => tr.children[idx].innerText || tr.children[idx].textContent;

    const comparer = (idx, asc) => (a, b) => ((v1, v2) => 
        v1 !== '' && v2 !== '' && !isNaN(v1) && !isNaN(v2) ? v1 - v2 : v1.toString().localeCompare(v2)
        )(getCellValue(asc ? a : b, idx), getCellValue(asc ? b : a, idx));

})();


