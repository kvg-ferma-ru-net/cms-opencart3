

function GetOrderId()
{
    let p = window.location.search;
    p = p.match(new RegExp('order_id' + '=([^&=]+)'));
    return p ? p[1] : false;
}

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

function CreateReceipt(event, receiptType) {
    event.preventDefault();
    let orderId = GetOrderId();
    let body = document.querySelector('#receipt-builder-window div.modal-body');
    const builder = new innokassa.ReceiptBuilder({
        element: body,
        receiptType:receiptType,
        canHeaderRender: false
    });
    builder.getReceipt().setOrderId(orderId);
    builder.setCallbackSend(() => {
        if (builder.getReceipt().reportValidity()) {
            console.log(builder.getReceipt().getRawObject());
        }
    });

    builder.setCallbackClose(() => {
        console.log('close');
    });

    builder.getReceipt().getItems().add(
        (new innokassa.ReceiptItem())
            .setName('name')
            .setPrice('500')
            .setQuantity(2)
            .setType(1)
            .setPaymentMethod(1)
            .setVat(6),
    );

    builder.getPrintables().add(
        (new innokassa.Printable())
            .setLink('qweqwe', 'https://innokassa.ru')
            .setSubType(1)
            .setAmount(1756)
            .setDate('2020'),
    );

    builder.render();

    let header = document.querySelector('#receipt-builder-window div.modal-header .modal-title');
    header.textContent = builder.getHeader();

    $("#receipt-builder-window").modal();
}

//##########################################################################

AddButtonFiscalization(document.querySelector('div.pull-right'));
