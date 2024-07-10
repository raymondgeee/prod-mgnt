import { Controller } from '@hotwired/stimulus';
import axios from 'axios';

export default class extends Controller {
    connect() {
        this.element.addEventListener('submit', this.submit.bind(this));
    }

    submit(event) {
        event.preventDefault();
        this.element.submit();
    }
}

