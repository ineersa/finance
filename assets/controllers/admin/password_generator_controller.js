import '../../styles/admin/password_generator.css';
import {Controller} from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://github.com/symfony/stimulus-bridge#lazy-controllers
*/
/* stimulusFetch: 'lazy' */
export default class extends Controller {
    constructor() {
        super(...arguments);
    }
    connect() {
        const button = this.createButton();
        this.element.insertAdjacentElement('afterend', button);
    }
    createButton() {
        const button = document.createElement('button');
        button.type = 'button';
        button.classList.add('password-generator-button');
        button.setAttribute('tabindex', '-2');
        button.addEventListener('click', this.generatePassword.bind(this));
        button.innerHTML = `<i class="fas fa-key"></i> Generate password`;
        return button;
    }
    generatePassword() {
        this.element.value = Array.from({length: 16})
            .map(() => (Math.random() * 36 | 0).toString(36))
            .join('');
    }
}

