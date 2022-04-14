(window["webpackJsonp"] = window["webpackJsonp"] || []).push([[6],{

/***/ "./node_modules/babel-loader/lib/index.js?!./node_modules/vue-loader/lib/index.js?!./node_modules/import-glob/index.js!./resources/scripts/admin/apps/settings/components/App.vue?vue&type=script&lang=js&":
/*!****************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib??ref--2!./node_modules/vue-loader/lib??vue-loader-options!./node_modules/import-glob!./resources/scripts/admin/apps/settings/components/App.vue?vue&type=script&lang=js& ***!
  \****************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
/* WEBPACK VAR INJECTION */(function($) {

Object.defineProperty(exports, "__esModule", {
	value: true
});

var _slicedToArray = function () { function sliceIterator(arr, i) { var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"]) _i["return"](); } finally { if (_d) throw _e; } } return _arr; } return function (arr, i) { if (Array.isArray(arr)) { return arr; } else if (Symbol.iterator in Object(arr)) { return sliceIterator(arr, i); } else { throw new TypeError("Invalid attempt to destructure non-iterable instance"); } }; }();

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _lodash = __webpack_require__(/*! lodash */ "./node_modules/lodash/lodash.js");

var _lodash2 = _interopRequireDefault(_lodash);

var _axios = __webpack_require__(/*! axios */ "./node_modules/axios/index.js");

var _axios2 = _interopRequireDefault(_axios);

var _vueMultiselect = __webpack_require__(/*! vue-multiselect */ "./node_modules/vue-multiselect/dist/vue-multiselect.min.js");

var _vueMultiselect2 = _interopRequireDefault(_vueMultiselect);

var _veeValidate = __webpack_require__(/*! vee-validate */ "./node_modules/vee-validate/dist/vee-validate.esm.js");

var _rules = __webpack_require__(/*! vee-validate/dist/rules */ "./node_modules/vee-validate/dist/rules.js");

var _bg = __webpack_require__(/*! vee-validate/dist/locale/bg.json */ "./node_modules/vee-validate/dist/locale/bg.json");

var _bg2 = _interopRequireDefault(_bg);

var _qs = __webpack_require__(/*! qs */ "./node_modules/qs/lib/index.js");

var _qs2 = _interopRequireDefault(_qs);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

(0, _veeValidate.localize)('bg', _bg2.default);
(0, _veeValidate.extend)('required', _extends({}, _rules.required, {
	message: 'Полето е задължително'
}));

(0, _veeValidate.extend)('email', _extends({}, _rules.email, {
	message: 'Полето трябва да е коректен Email адрес'
}));

exports.default = {
	components: { Multiselect: _vueMultiselect2.default },
	data: function data() {
		return {
			loading: false,
			fields: _lodash2.default.cloneDeep(wooBg_settings.fields),
			groups_titles: _lodash2.default.cloneDeep(wooBg_settings.groups_titles),
			message: ''
		};
	},
	mounted: function mounted() {
		$(document.body).trigger('init_tooltips');
	},

	methods: {
		runSubmit: function runSubmit() {
			var fieldsForSubmit = {};
			var _iteratorNormalCompletion = true;
			var _didIteratorError = false;
			var _iteratorError = undefined;

			try {
				for (var _iterator = Object.entries(this.fields)[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
					var _ref = _step.value;

					var _ref2 = _slicedToArray(_ref, 2);

					var group = _ref2[0];
					var fields = _ref2[1];

					fieldsForSubmit[group] = {};

					var _iteratorNormalCompletion2 = true;
					var _didIteratorError2 = false;
					var _iteratorError2 = undefined;

					try {
						for (var _iterator2 = Object.entries(fields)[Symbol.iterator](), _step2; !(_iteratorNormalCompletion2 = (_step2 = _iterator2.next()).done); _iteratorNormalCompletion2 = true) {
							var _ref3 = _step2.value;

							var _ref4 = _slicedToArray(_ref3, 2);

							var name = _ref4[0];
							var props = _ref4[1];

							fieldsForSubmit[group][name] = {
								value: props.value,
								type: props.type
							};
						}
					} catch (err) {
						_didIteratorError2 = true;
						_iteratorError2 = err;
					} finally {
						try {
							if (!_iteratorNormalCompletion2 && _iterator2.return) {
								_iterator2.return();
							}
						} finally {
							if (_didIteratorError2) {
								throw _iteratorError2;
							}
						}
					}
				}
			} catch (err) {
				_didIteratorError = true;
				_iteratorError = err;
			} finally {
				try {
					if (!_iteratorNormalCompletion && _iterator.return) {
						_iterator.return();
					}
				} finally {
					if (_didIteratorError) {
						throw _iteratorError;
					}
				}
			}

			this.loading = true;
			var _this = this;
			var data = {
				action: 'woo_bg_save_settings',
				options: fieldsForSubmit,
				tab: wooBg_settings.tab,
				nonce: wooBg_settings.nonce
			};

			_axios2.default.post(woocommerce_admin.ajax_url, _qs2.default.stringify(data)).then(function (response) {
				if (response.data.data.message) {
					_this.message = response.data.data.message;
				}

				if (response.data.data.fields) {
					_this.fields = {};
					_this.groups_titles = {};
					_this.fields = _lodash2.default.cloneDeep(response.data.data.fields);
					_this.groups_titles = _lodash2.default.cloneDeep(response.data.data.groups_titles);
				}

				_this.loading = false;
			}).catch(function (error) {
				_this.message = "Имаше проблем със запазването на настройките. За повече информация вижте конзолата.";
				console.log(error);
				_this.loading = false;
			});
		}
	}
};
/* WEBPACK VAR INJECTION */}.call(this, __webpack_require__(/*! jquery */ "jquery")))

/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./resources/scripts/admin/apps/settings/components/App.vue?vue&type=template&id=16f20a36&":
/*!*******************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib??vue-loader-options!./resources/scripts/admin/apps/settings/components/App.vue?vue&type=template&id=16f20a36& ***!
  \*******************************************************************************************************************************************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "render", function() { return render; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return staticRenderFns; });
var render = function () {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c(
    "div",
    { staticClass: "ajax-container", attrs: { "data-loading": _vm.loading } },
    [
      _c("ValidationObserver", {
        ref: "form",
        scopedSlots: _vm._u([
          {
            key: "default",
            fn: function (ref) {
              var handleSubmit = ref.handleSubmit
              return [
                _c(
                  "form",
                  {
                    on: {
                      submit: function ($event) {
                        $event.preventDefault()
                        return handleSubmit(_vm.runSubmit)
                      },
                    },
                  },
                  [
                    _vm._l(_vm.fields, function (group, group_slug) {
                      return _c("div", [
                        _c("h2", [
                          _vm._v(_vm._s(_vm.groups_titles[group_slug].title)),
                        ]),
                        _vm._v(" "),
                        _c("table", { staticClass: "form-table" }, [
                          _c(
                            "tbody",
                            _vm._l(group, function (field, field_slug) {
                              return field.type === "text"
                                ? _c(
                                    "tr",
                                    { attrs: { valign: "top" } },
                                    [
                                      _c(
                                        "th",
                                        {
                                          staticClass: "titledesc",
                                          attrs: { scope: "row" },
                                        },
                                        [
                                          _c(
                                            "label",
                                            {
                                              attrs: {
                                                for: "woo-bg-" + field.name,
                                              },
                                            },
                                            [
                                              _vm._v(
                                                "\n\t\t\t\t\t\t\t\t\t" +
                                                  _vm._s(field.title) +
                                                  "\n\n\t\t\t\t\t\t\t\t\t"
                                              ),
                                              field.help_text
                                                ? _c("span", {
                                                    staticClass:
                                                      "woocommerce-help-tip",
                                                    attrs: {
                                                      "data-tip":
                                                        field.help_text,
                                                    },
                                                  })
                                                : _vm._e(),
                                            ]
                                          ),
                                        ]
                                      ),
                                      _vm._v(" "),
                                      _c("ValidationProvider", {
                                        staticClass: "forminp forminp-text",
                                        attrs: {
                                          tag: "td",
                                          rules: field.validation_rules,
                                        },
                                        scopedSlots: _vm._u(
                                          [
                                            {
                                              key: "default",
                                              fn: function (ref) {
                                                var errors = ref.errors
                                                return [
                                                  _c("input", {
                                                    directives: [
                                                      {
                                                        name: "model",
                                                        rawName: "v-model",
                                                        value:
                                                          _vm.fields[
                                                            group_slug
                                                          ][field_slug].value,
                                                        expression:
                                                          "fields[group_slug][field_slug].value",
                                                      },
                                                    ],
                                                    attrs: {
                                                      name:
                                                        "woo-bg-" + field.name,
                                                      type: "text",
                                                      placeholder: field.title,
                                                    },
                                                    domProps: {
                                                      value:
                                                        _vm.fields[group_slug][
                                                          field_slug
                                                        ].value,
                                                    },
                                                    on: {
                                                      input: function ($event) {
                                                        if (
                                                          $event.target
                                                            .composing
                                                        ) {
                                                          return
                                                        }
                                                        _vm.$set(
                                                          _vm.fields[
                                                            group_slug
                                                          ][field_slug],
                                                          "value",
                                                          $event.target.value
                                                        )
                                                      },
                                                    },
                                                  }),
                                                  _vm._v(" "),
                                                  field.description
                                                    ? _c(
                                                        "p",
                                                        {
                                                          staticClass:
                                                            "description",
                                                        },
                                                        [
                                                          _vm._v(
                                                            "\n\t\t\t\t\t\t\t\t\t" +
                                                              _vm._s(
                                                                field.description
                                                              ) +
                                                              "\n\t\t\t\t\t\t\t\t"
                                                          ),
                                                        ]
                                                      )
                                                    : _vm._e(),
                                                  _vm._v(" "),
                                                  _c(
                                                    "p",
                                                    {
                                                      staticClass:
                                                        "field-error",
                                                    },
                                                    [_vm._v(_vm._s(errors[0]))]
                                                  ),
                                                ]
                                              },
                                            },
                                          ],
                                          null,
                                          true
                                        ),
                                      }),
                                    ],
                                    1
                                  )
                                : field.type === "select"
                                ? _c("tr", { attrs: { valign: "top" } }, [
                                    _c(
                                      "th",
                                      {
                                        staticClass: "titledesc",
                                        attrs: { scope: "row" },
                                      },
                                      [
                                        _c(
                                          "label",
                                          {
                                            attrs: {
                                              for:
                                                "woo-bg-gateway-" +
                                                group_slug +
                                                "-" +
                                                field_slug,
                                            },
                                          },
                                          [
                                            _vm._v(
                                              "\n\t\t\t\t\t\t\t\t\t" +
                                                _vm._s(field.title) +
                                                " \n\n\t\t\t\t\t\t\t\t\t"
                                            ),
                                            field.help_text
                                              ? _c("span", {
                                                  staticClass:
                                                    "woocommerce-help-tip",
                                                  attrs: {
                                                    "data-tip": field.help_text,
                                                  },
                                                })
                                              : _vm._e(),
                                          ]
                                        ),
                                      ]
                                    ),
                                    _vm._v(" "),
                                    _c(
                                      "td",
                                      { staticClass: "forminp forminp-text" },
                                      [
                                        _c("multiselect", {
                                          attrs: {
                                            "deselect-label": "",
                                            selectLabel: "",
                                            "track-by": "id",
                                            label: "label",
                                            selectedLabel: "Избрано",
                                            placeholder: "Изберете",
                                            options: Object.values(
                                              _vm.fields[group_slug][field_slug]
                                                .options
                                            ),
                                            searchable: true,
                                            "allow-empty": false,
                                          },
                                          scopedSlots: _vm._u(
                                            [
                                              {
                                                key: "singleLabel",
                                                fn: function (ref) {
                                                  var option = ref.option
                                                  return [
                                                    _c("strong", [
                                                      _vm._v(
                                                        _vm._s(option.label)
                                                      ),
                                                    ]),
                                                  ]
                                                },
                                              },
                                            ],
                                            null,
                                            true
                                          ),
                                          model: {
                                            value:
                                              _vm.fields[group_slug][field_slug]
                                                .value,
                                            callback: function ($$v) {
                                              _vm.$set(
                                                _vm.fields[group_slug][
                                                  field_slug
                                                ],
                                                "value",
                                                $$v
                                              )
                                            },
                                            expression:
                                              "fields[group_slug][field_slug].value",
                                          },
                                        }),
                                        _vm._v(" "),
                                        field.description
                                          ? _c("p", {
                                              staticClass: "description",
                                              domProps: {
                                                innerHTML: _vm._s(
                                                  field.description
                                                ),
                                              },
                                            })
                                          : _vm._e(),
                                      ],
                                      1
                                    ),
                                  ])
                                : _vm._e()
                            }),
                            0
                          ),
                        ]),
                      ])
                    }),
                    _vm._v(" "),
                    _c("p", { staticClass: "submit" }, [
                      _c(
                        "button",
                        {
                          staticClass: "button-primary woocommerce-save-button",
                          attrs: {
                            name: "save",
                            type: "submit",
                            value: "Запазване на промените",
                          },
                        },
                        [_vm._v("Запазване на промените")]
                      ),
                      _vm._v(" "),
                      _c("span", { staticClass: "form-message" }, [
                        _vm._v(_vm._s(_vm.message)),
                      ]),
                    ]),
                  ],
                  2
                ),
              ]
            },
          },
        ]),
      }),
    ],
    1
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./resources/scripts/admin/apps/settings/app.js":
/*!******************************************************!*\
  !*** ./resources/scripts/admin/apps/settings/app.js ***!
  \******************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
	value: true
});

__webpack_require__(/*! es6-promise/auto */ "./node_modules/es6-promise/auto.js");

var _vue = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");

var _vue2 = _interopRequireDefault(_vue);

var _App = __webpack_require__(/*! ./components/App.vue */ "./resources/scripts/admin/apps/settings/components/App.vue");

var _App2 = _interopRequireDefault(_App);

var _veeValidate = __webpack_require__(/*! vee-validate */ "./node_modules/vee-validate/dist/vee-validate.esm.js");

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

_vue2.default.component('ValidationProvider', _veeValidate.ValidationProvider);
_vue2.default.component('ValidationObserver', _veeValidate.ValidationObserver);

exports.default = new _vue2.default({
	el: '#woo-bg-settings',
	render: function render(h) {
		return h(_App2.default);
	}
});

/***/ }),

/***/ "./resources/scripts/admin/apps/settings/components/App.vue":
/*!******************************************************************!*\
  !*** ./resources/scripts/admin/apps/settings/components/App.vue ***!
  \******************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _App_vue_vue_type_template_id_16f20a36___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./App.vue?vue&type=template&id=16f20a36& */ "./resources/scripts/admin/apps/settings/components/App.vue?vue&type=template&id=16f20a36&");
/* harmony import */ var _App_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./App.vue?vue&type=script&lang=js& */ "./resources/scripts/admin/apps/settings/components/App.vue?vue&type=script&lang=js&");
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _App_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__) if(["default"].indexOf(__WEBPACK_IMPORT_KEY__) < 0) (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _App_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__[key]; }) }(__WEBPACK_IMPORT_KEY__));
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */

var component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _App_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _App_vue_vue_type_template_id_16f20a36___WEBPACK_IMPORTED_MODULE_0__["render"],
  _App_vue_vue_type_template_id_16f20a36___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"],
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "resources/scripts/admin/apps/settings/components/App.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./resources/scripts/admin/apps/settings/components/App.vue?vue&type=script&lang=js&":
/*!*******************************************************************************************!*\
  !*** ./resources/scripts/admin/apps/settings/components/App.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_ref_2_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_import_glob_index_js_App_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/babel-loader/lib??ref--2!../../../../../../node_modules/vue-loader/lib??vue-loader-options!../../../../../../node_modules/import-glob!./App.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js?!./node_modules/vue-loader/lib/index.js?!./node_modules/import-glob/index.js!./resources/scripts/admin/apps/settings/components/App.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_babel_loader_lib_index_js_ref_2_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_import_glob_index_js_App_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_babel_loader_lib_index_js_ref_2_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_import_glob_index_js_App_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _node_modules_babel_loader_lib_index_js_ref_2_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_import_glob_index_js_App_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__) if(["default"].indexOf(__WEBPACK_IMPORT_KEY__) < 0) (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _node_modules_babel_loader_lib_index_js_ref_2_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_import_glob_index_js_App_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_ref_2_node_modules_vue_loader_lib_index_js_vue_loader_options_node_modules_import_glob_index_js_App_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "./resources/scripts/admin/apps/settings/components/App.vue?vue&type=template&id=16f20a36&":
/*!*************************************************************************************************!*\
  !*** ./resources/scripts/admin/apps/settings/components/App.vue?vue&type=template&id=16f20a36& ***!
  \*************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_App_vue_vue_type_template_id_16f20a36___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../../../node_modules/vue-loader/lib??vue-loader-options!./App.vue?vue&type=template&id=16f20a36& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./resources/scripts/admin/apps/settings/components/App.vue?vue&type=template&id=16f20a36&");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "render", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_App_vue_vue_type_template_id_16f20a36___WEBPACK_IMPORTED_MODULE_0__["render"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_App_vue_vue_type_template_id_16f20a36___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"]; });



/***/ }),

/***/ 0:
/*!********************************!*\
  !*** ./util.inspect (ignored) ***!
  \********************************/
/*! no static exports found */
/***/ (function(module, exports) {

/* (ignored) */

/***/ })

}]);
//# sourceMappingURL=41017c8737acd634c5bc.js.map