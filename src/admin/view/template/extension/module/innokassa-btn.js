

/**
 * Получить идентификатор заказа
 * @returns {number|false}
 */
function GetOrderId()
{
    let p = window.location.search;
    p = p.match(new RegExp('order_id' + '=([^&=]+)'));
    return (p ? p[1] : false);
}

// #########################################################################

/**
 * Добавить кнопки фискализации 
 * @param {HTMLElement} element 
 */
function AddButtonToCrm(element) {
    let link = document.createElement('a');
    link.classList.add('btn', 'btn-primary');
    link.href = 'https://crm.innokassa.ru/';
    link.target = 'blank';
    link.textContent = 'CRM.Innokassa';
    link.ariaExpanded = false;

    element.prepend(link);
}

//##########################################################################

// если идентификатор заказа есть тогда вставляем кнопки
if (GetOrderId()) {
    AddButtonToCrm(document.querySelector('div.pull-right'));
}
