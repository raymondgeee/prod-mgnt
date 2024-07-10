// assets/controllers/index.js

import { Application } from '@hotwired/stimulus';
import FilterController from './filter_controller';

const application = Application.start();
application.register('filter', FilterController);


// import { definitionsFromContext } from '@hotwired/stimulus-webpack-helpers';

// // Initialize Stimulus application
// const application = Application.start();

// // Automatically import all Stimulus controllers
// const context = require.context('.', true, /\.js$/);
// application.load(definitionsFromContext(context));
