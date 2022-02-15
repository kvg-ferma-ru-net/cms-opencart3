

/**
 * Получить идентификатор заказа
 * @returns {number|false}
 */
function GetOrderId()
{
    let p = window.location.search;
    p = p.match(new RegExp('order_id' + '=([^&=]+)'));
    return p ? p[1] : false;
}

/**
 * Получить user_token
 * @returns {string|false}
 */
function GetUserToken()
{
    let p = window.location.search;
    p = p.match(new RegExp('user_token' + '=([^&=]+)'));
    return p ? p[1] : false;
}

// #########################################################################

/**
 * Добавить кнопки фискализации 
 * @param {HTMLElement} element 
 */
function AddButtonFiscalization(element) {
    let buttonGroup = document.createElement('div');
    buttonGroup.classList.add('btn', 'dropdown');

    let button = document.createElement('button');
    button.type = 'button';
    button.classList.add('btn', 'btn-primary', 'dropdown-toggle');
    button.textContent = 'Создать чек';
    button.ariaExpanded = false;
    button.setAttribute('data-toggle', 'dropdown');
    buttonGroup.append(button);


    let ul = document.createElement('ul');
    buttonGroup.append(ul);
    ul.classList.add('dropdown-menu');

    let li1 = document.createElement('li');
    ul.append(li1);
    let a1 = document.createElement('a');
    a1.href = '#';
    a1.textContent = 'Приход';
    a1.onclick = (event) => {
        CreateReceipt(event, 1);
    }
    li1.append(a1);

    let li2 = document.createElement('li');
    ul.append(li2);
    let a2 = document.createElement('a');
    a2.href = '#';
    a2.textContent = 'Возврат';
    a2.onclick = (event) => {
        CreateReceipt(event, 2);
    }
    li2.append(a2);

    element.prepend(buttonGroup);
}

/**
 * Обработчик создания чека
 * @param {*} event объект события
 * @param {number} receiptType тип чека (1 - приход, 2 - возврат)
 */
function CreateReceipt(event, receiptType) {
    event.preventDefault();

    let orderId = GetOrderId();
    let userToken = GetUserToken();

    $.ajax({
        url: `/admin/index.php?route=extension/module/innokassa/ajaxGetOrder&user_token=${userToken}&order_id=${orderId}`,
        type: 'get',
        crossDomain: true,
        success: function(json) {

            console.log(json);

            if (!json.success) {
                alert(json.error);
                return;
            }

            let body = document.querySelector('#receipt-builder-window div.modal-body');
            const builder = new innokassa.ReceiptBuilder({
                element: body,
                receiptType: receiptType,
                canHeaderRender: false
            });

            let receipt = builder.getReceipt();
            receipt.setOrderId(orderId);

            let notify = receipt.getNotify();

            if (json.notify.email) {
                notify.setEmail(json.notify.email);
            }

            if (json.notify.phone) {
                notify.setPhone(json.notify.phone);
            }

            json.items.forEach((item) => {
                receipt.getItems().add(
                    (new innokassa.ReceiptItem())
                        .setName(item.name)
                        .setPrice(item.price)
                        .setQuantity(item.quantity)
                        .setType(item.type)
                        .setPaymentMethod(item.payment_method)
                        .setVat(item.vat)
                );
            });

            json.printables.forEach((printable) => {
                builder.getPrintables().add(
                    (new innokassa.Printable())
                        .setLink(printable.uuid, printable.link)
                        .setStatus(printable.status)
                        .setType(printable.type)
                        .setSubType(printable.subType)
                        .setAmount(printable.amount)
                );
            });

            builder.setCallbackSend(() => {
                if (builder.getReceipt().reportValidity()) {
                    const receiptRaw = receipt.getRawObject();

                    //console.log(receipt.getRawObject());
                    //return;

                    $.ajax({
                        url: `/admin/index.php?route=extension/module/innokassa/ajaxHandFiscal&user_token=${userToken}&order_id=${orderId}`,
                        type: 'post',
                        data: receiptRaw,
                        crossDomain: true,
                        success: function(json) {
                            if (json.success) {
                                alert('Чек поставлен в очередь на фискализацию');
                                window.location.reload();
                            } else {
                                alert(json.error);
                            }
                        }
                    });
                }
            });

            builder.setCallbackClose(() => {
                $("#receipt-builder-window").modal("hide");
            });

            builder.render();

            let header = document.querySelector('#receipt-builder-window div.modal-header .modal-title');
            header.textContent = builder.getHeader();

            $("#receipt-builder-window").modal();
        }
    });
}

//##########################################################################

// если идентификатор заказа есть тогда вставляем кнопки
if (GetOrderId()) {
    AddButtonFiscalization(document.querySelector('div.pull-right'));
}
