<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class ps_html extends Module
{
    public function __construct()
    {
        $this->name = 'ps_html';
        $this->tab = 'front_office_features';
        $this->version = '2.0.0';
        $this->author = 'lidiapdiaz';
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Bloque HTML');
        $this->description = $this->l('Muestra un bloque HTML personalizable en el pie del modal del carrito y arriba del carrito de compra');
    }

    public function install()
    {
        return parent::install()
            && $this->createCustomHooks()
            && $this->registerHook('displayFooterModalCart')
            && $this->registerHook('displayShoppingCartTop')
            && Configuration::updateValue('CUSTOMMODALFOOTER_HTML', '')
            && Configuration::updateValue('CUSTOMMODALFOOTER_CART_RULES', '')
            && Configuration::updateValue('CUSTOMMODALFOOTER_PRODUCTS', '')
            && Configuration::updateValue('CUSTOMMODALFOOTER_BRANDS', '')
            && Configuration::updateValue('CUSTOMMODALFOOTER_CATEGORIES', '');
    }

    private function createCustomHooks()
    {
        $hooksToCreate = [
            'displayFooterModalCart' => 'Display Footer in Modal Cart',
            'displayShoppingCartTop' => 'Display Shopping Cart Top',
        ];

        foreach ($hooksToCreate as $hookName => $hookTitle) {
            if (!Hook::getIdByName($hookName)) {
                $hook = new Hook();
                $hook->name = $hookName;
                $hook->title = $hookTitle;
                $hook->description = 'Custom hook for ' . $hookTitle;
                $hook->add();
            }
        }

        return true;
    }

    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName('CUSTOMMODALFOOTER_HTML')
            && Configuration::deleteByName('CUSTOMMODALFOOTER_CART_RULES')
            && Configuration::deleteByName('CUSTOMMODALFOOTER_PRODUCTS')
            && Configuration::deleteByName('CUSTOMMODALFOOTER_BRANDS')
            && Configuration::deleteByName('CUSTOMMODALFOOTER_CATEGORIES');
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submitCustomModalFooter')) {
            $html = Tools::getValue('CUSTOMMODALFOOTER_HTML', '');
            Configuration::updateValue('CUSTOMMODALFOOTER_HTML', $html, true);

            $cart_rules = $this->cleanIdList(Tools::getValue('CUSTOMMODALFOOTER_CART_RULES', ''));
            $products = $this->cleanIdList(Tools::getValue('CUSTOMMODALFOOTER_PRODUCTS', ''));
            $brands = $this->cleanIdList(Tools::getValue('CUSTOMMODALFOOTER_BRANDS', ''));
            $categories = $this->cleanIdList(Tools::getValue('CUSTOMMODALFOOTER_CATEGORIES', ''));

            Configuration::updateValue('CUSTOMMODALFOOTER_CART_RULES', $cart_rules);
            Configuration::updateValue('CUSTOMMODALFOOTER_PRODUCTS', $products);
            Configuration::updateValue('CUSTOMMODALFOOTER_BRANDS', $brands);
            Configuration::updateValue('CUSTOMMODALFOOTER_CATEGORIES', $categories);

            $output .= $this->displayConfirmation($this->l('Configuración guardada'));
        }
        return $output . $this->renderForm();
    }

    private function cleanIdList($str)
    {
        $arr = array_filter(array_map('trim', explode(',', $str)), function ($id) {
            return ctype_digit($id);
        });
        return implode(',', $arr);
    }

    private function renderForm()
    {
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

        $fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->l('Configuración del HTML'),
            ],
            'input' => [
                [
                    'type' => 'textarea',
                    'label' => $this->l('Código HTML'),
                    'name' => 'CUSTOMMODALFOOTER_HTML',
                    'autoload_rte' => true,
                    'rows' => 10,
                    'cols' => 60,
                    'desc' => $this->l('Introduce el HTML que se mostrará en el bloque'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('IDs de reglas de carrito'),
                    'name' => 'CUSTOMMODALFOOTER_CART_RULES',
                    'desc' => $this->l('Lista de IDs separadas por comas de reglas de carrito para mostrar el bloque (vacío = sin filtro)'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('IDs de productos'),
                    'name' => 'CUSTOMMODALFOOTER_PRODUCTS',
                    'desc' => $this->l('Lista de IDs separadas por comas de productos para mostrar el bloque (vacío = sin filtro)'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('IDs de marcas'),
                    'name' => 'CUSTOMMODALFOOTER_BRANDS',
                    'desc' => $this->l('Lista de IDs separadas por comas de marcas para mostrar el bloque (vacío = sin filtro)'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('IDs de categorías'),
                    'name' => 'CUSTOMMODALFOOTER_CATEGORIES',
                    'desc' => $this->l('Lista de IDs separadas por comas de categorías para mostrar el bloque (vacío = sin filtro)'),
                ],
            ],
            'submit' => [
                'title' => $this->l('Guardar'),
            ]
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;
        $helper->title = $this->displayName;
        $helper->show_toolbar = false;
        $helper->toolbar_scroll = false;
        $helper->submit_action = 'submitCustomModalFooter';

        $helper->fields_value['CUSTOMMODALFOOTER_HTML'] = Configuration::get('CUSTOMMODALFOOTER_HTML', '');
        $helper->fields_value['CUSTOMMODALFOOTER_CART_RULES'] = Configuration::get('CUSTOMMODALFOOTER_CART_RULES', '');
        $helper->fields_value['CUSTOMMODALFOOTER_PRODUCTS'] = Configuration::get('CUSTOMMODALFOOTER_PRODUCTS', '');
        $helper->fields_value['CUSTOMMODALFOOTER_BRANDS'] = Configuration::get('CUSTOMMODALFOOTER_BRANDS', '');
        $helper->fields_value['CUSTOMMODALFOOTER_CATEGORIES'] = Configuration::get('CUSTOMMODALFOOTER_CATEGORIES', '');

        return $helper->generateForm($fieldsForm);
    }

    /**
     * Lógica para mostrar mensaje en el modal sólo si el último producto añadido cumple los filtros
     */
    private function shouldDisplayBlockForLastProduct($cart, $params)
    {
        $cartRulesConfig = Configuration::get('CUSTOMMODALFOOTER_CART_RULES', '');
        $productsConfig = Configuration::get('CUSTOMMODALFOOTER_PRODUCTS', '');
        $brandsConfig = Configuration::get('CUSTOMMODALFOOTER_BRANDS', '');
        $categoriesConfig = Configuration::get('CUSTOMMODALFOOTER_CATEGORIES', '');

        $cartRules = array_filter(array_map('trim', explode(',', $cartRulesConfig)));
        $products = array_filter(array_map('trim', explode(',', $productsConfig)));
        $brands = array_filter(array_map('trim', explode(',', $brandsConfig)));
        $categories = array_filter(array_map('trim', explode(',', $categoriesConfig)));

        // Si no hay filtros, mostrar siempre
        if (empty($cartRules) && empty($products) && empty($brands) && empty($categories)) {
            return true;
        }

        // Si hay reglas de carrito y ninguna coincide, no mostrar
        if (!empty($cartRules)) {
            $cartRuleIds = [];
            foreach ($cart->getCartRules() as $rule) {
                if (isset($rule['id_cart_rule'])) {
                    $cartRuleIds[] = (string)$rule['id_cart_rule'];
                }
            }
            if (count(array_intersect($cartRules, $cartRuleIds)) === 0) {
                return false;
            }
        }

        // Obtener último producto añadido (intenta obtenerlo de $params)
        if (!empty($params['product']) && is_array($params['product'])) {
            $product = $params['product'];
        } elseif (!empty($params['id_product'])) {
            $prodObj = new Product((int)$params['id_product']);
            $product = [
                'id_product' => $prodObj->id,
                'id_manufacturer' => $prodObj->id_manufacturer,
                'id_category_default' => $prodObj->id_category_default,
            ];
        } else {
            // Si no tenemos información, asumimos no mostrar
            return false;
        }

        // Comprobación filtros en producto
        if (!empty($products) && in_array((string)$product['id_product'], $products)) {
            return true;
        }

        if (!empty($brands) && !empty($product['id_manufacturer']) && in_array((string)$product['id_manufacturer'], $brands)) {
            return true;
        }

        if (!empty($categories) && !empty($product['id_category_default']) && in_array((string)$product['id_category_default'], $categories)) {
            return true;
        }

        // No cumple ningún filtro
        return false;
    }

    /**
     * Para el bloque arriba del carrito mostramos si **algún producto** cumple filtros (o siempre si no hay filtro)
     */
    private function shouldDisplayBlockForCart($cart)
    {
        $cartRulesConfig = Configuration::get('CUSTOMMODALFOOTER_CART_RULES', '');
        $productsConfig = Configuration::get('CUSTOMMODALFOOTER_PRODUCTS', '');
        $brandsConfig = Configuration::get('CUSTOMMODALFOOTER_BRANDS', '');
        $categoriesConfig = Configuration::get('CUSTOMMODALFOOTER_CATEGORIES', '');

        $cartRules = array_filter(array_map('trim', explode(',', $cartRulesConfig)));
        $products = array_filter(array_map('trim', explode(',', $productsConfig)));
        $brands = array_filter(array_map('trim', explode(',', $brandsConfig)));
        $categories = array_filter(array_map('trim', explode(',', $categoriesConfig)));

        // Si no hay filtros, mostrar siempre
        if (empty($cartRules) && empty($products) && empty($brands) && empty($categories)) {
            return true;
        }

        // Si hay reglas de carrito y ninguna coincide, no mostrar
        if (!empty($cartRules)) {
            $cartRuleIds = [];
            foreach ($cart->getCartRules() as $rule) {
                if (isset($rule['id_cart_rule'])) {
                    $cartRuleIds[] = (string)$rule['id_cart_rule'];
                }
            }
            if (count(array_intersect($cartRules, $cartRuleIds)) === 0) {
                return false;
            }
        }

        // Comprobar si algún producto cumple filtros
        foreach ($cart->getProducts() as $product) {
            if (!empty($products) && in_array((string)$product['id_product'], $products)) {
                return true;
            }
            if (!empty($brands) && !empty($product['id_manufacturer']) && in_array((string)$product['id_manufacturer'], $brands)) {
                return true;
            }
            if (!empty($categories) && !empty($product['id_category_default']) && in_array((string)$product['id_category_default'], $categories)) {
                return true;
            }
        }

        // Ningún producto cumple filtro
        return false;
    }

public function hookDisplayFooterModalCart($params)
{
    $cart = Context::getContext()->cart;
    if (!$cart) {
        return '';
    }

    // Obtener productos del carrito
    $productsInCart = $cart->getProducts();

    // Obtener filtros de configuración
    $filters = [
        'products' => array_filter(array_map('trim', explode(',', Configuration::get('CUSTOMMODALFOOTER_PRODUCTS', '')))),
        'brands' => array_filter(array_map('trim', explode(',', Configuration::get('CUSTOMMODALFOOTER_BRANDS', '')))),
        'categories' => array_filter(array_map('trim', explode(',', Configuration::get('CUSTOMMODALFOOTER_CATEGORIES', '')))),
    ];

    // Si no hay filtros configurados, mostrar mensaje siempre
    $noFilters = empty($filters['products']) && empty($filters['brands']) && empty($filters['categories']);

    if (!isset($_SESSION)) {
        session_start();
    }
    if (!isset($_SESSION['ps_html_shown_for'])) {
        $_SESSION['ps_html_shown_for'] = [];
    }

    $showMessage = false;

    foreach ($productsInCart as $product) {
        $productIdStr = (string)$product['id_product'];

        // Si ya mostramos mensaje para este producto, saltar
        if (in_array($productIdStr, $_SESSION['ps_html_shown_for'])) {
            continue;
        }

        // Si no hay filtros, mostrar mensaje y marcar producto como mostrado
        if ($noFilters) {
            $_SESSION['ps_html_shown_for'][] = $productIdStr;
            $showMessage = true;
            break; // Basta con uno para mostrar mensaje
        }

        // Verificar si producto cumple alguna condición (OR)
        $matchesFilter = false;

        if (!empty($filters['products']) && in_array($productIdStr, $filters['products'])) {
            $matchesFilter = true;
        }
        if (!$matchesFilter && !empty($filters['brands']) && !empty($product['id_manufacturer']) && in_array((string)$product['id_manufacturer'], $filters['brands'])) {
            $matchesFilter = true;
        }
        if (!$matchesFilter && !empty($filters['categories']) && !empty($product['id_category_default']) && in_array((string)$product['id_category_default'], $filters['categories'])) {
            $matchesFilter = true;
        }

        if ($matchesFilter) {
            $_SESSION['ps_html_shown_for'][] = $productIdStr;
            $showMessage = true;
            break; // Basta con uno que cumpla para mostrar mensaje
        }
    }

    if (!$showMessage) {
        return '';
    }

    $this->context->smarty->assign([
        'custom_html' => Configuration::get('CUSTOMMODALFOOTER_HTML', ''),
    ]);

    return $this->display(__FILE__, 'views/templates/hook/ps_html.tpl');
}

    public function hookDisplayShoppingCartTop($params)
    {
        $cart = Context::getContext()->cart;
        if (!$cart) {
            return '';
        }

        // Mostrar si algún producto cumple filtros
        if (!$this->shouldDisplayBlockForCart($cart)) {
            return '';
        }

        $this->context->smarty->assign([
            'custom_html' => Configuration::get('CUSTOMMODALFOOTER_HTML', ''),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/ps_html.tpl');
    }
}

