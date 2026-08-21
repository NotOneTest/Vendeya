@extends('vendeya.layouts.app')

@section('title', 'Vendeya POS')
@section('body-class', 'pos-page')

@section('content')
<div class="pos-container">
    <header class="pos-header">
        <div class="header-left">
            <div class="logo"><img src="{{ $logo }}" alt="FactReady Lite" style="height: 32px; object-fit: contain;"></div>
        </div>
        <div class="header-right">
            <button class="header-btn" id="themeToggle" title="Cambiar tema"><i class="fas fa-moon"></i></button>
            <button class="header-btn" id="settingsBtn" title="Configuración"><i class="fas fa-cog"></i></button>
            <div class="user-info">
                <span>{{ $user['name'] ?? 'Usuario' }}</span>
                <a href="{{ route('vendeya.logout') }}" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
    </header>

    <!-- Settings Panel -->
    <div class="settings-overlay" id="settingsOverlay"></div>
    <aside class="settings-panel" id="settingsPanel">
        <div class="settings-content">
            <button class="settings-close" id="settingsClose"><i class="fas fa-times"></i></button>
            
            <div class="settings-header-section">
                <div class="settings-avatar">
                    <img src="{{ $logo }}" alt="FactReady Lite" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <h3 class="settings-company">FactReady Lite</h3>
                <p class="settings-branch">OFICINA PRINCIPAL</p>
            </div>

            <div class="settings-user-info">
                <p class="settings-user-name">{{ $user['name'] ?? 'Demo' }}</p>
                <p class="settings-user-email">{{ $user['email'] ?? 'demo@gmail.com' }}</p>
            </div>

            <div class="settings-options">
                <div class="settings-option">
                    <div class="option-icon"><i class="fas fa-globe"></i></div>
                    <div class="option-text">
                        <span class="option-title">Dominio</span>
                        <span class="option-value">{{ $apiDomain }}</span>
                    </div>
                </div>

                <div class="settings-option" id="btnSelectPrinter">
                    <div class="option-icon"><i class="fas fa-print"></i></div>
                    <div class="option-text">
                        <span class="option-title">Seleccionar impresora</span>
                        <span class="option-subtitle">Vincular a QZ Tray</span>
                    </div>
                </div>

                <div class="settings-option disabled">
                    <div class="option-icon"><i class="fas fa-cloud-download-alt"></i></div>
                    <div class="option-text">
                        <span class="option-title">Descargar datos de la nube</span>
                        <span class="option-subtitle">Sincronizar información</span>
                    </div>
                </div>

                <div class="settings-option" id="btnViewCash">
                    <div class="option-icon"><i class="fas fa-eye"></i></div>
                    <div class="option-text">
                        <span class="option-title">Ver Caja</span>
                        <span class="option-subtitle">Detalles de la jornada</span>
                    </div>
                </div>

                <div class="settings-option" id="closeCashOption">
                    <div class="option-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <div class="option-text">
                        <span class="option-title">Cerrar caja y enviar a la nube</span>
                        <span class="option-subtitle">Finalizar jornada</span>
                    </div>
                </div>
            </div>

            <button class="btn-settings-exit" id="btnSettingsExit">
                <i class="fas fa-sign-out-alt"></i>
                Salir
            </button>
        </div>
    </aside>

    <main class="pos-main">
        <section class="products-section">
            <div class="category-filters">
                <div class="category-search-wrapper">
                    <i class="fas fa-search category-search-icon"></i>
                    <input type="text" class="category-search-input" id="categorySearchInput" placeholder="Buscar categoría...">
                </div>
                <div class="category-buttons">
                    @foreach($allCategories as $category)
                    <button class="category-btn {{ $loop->first ? 'active' : '' }}" data-category="{{ $category }}">{{ $category }}</button>
                    @endforeach
                </div>
            </div>
            <div class="search-bar">
                <i class="fas fa-search search-icon"></i>
                <input type="text" placeholder="Busca un producto ..." id="searchInput" autocomplete="off">
                <div class="search-results" id="searchResults"></div>
                <i class="fas fa-barcode scan-icon"></i>
            </div>
            <div class="view-toggle">
                <button class="view-btn active" data-view="grid"><i class="fas fa-th-large"></i></button>
                <button class="view-btn" data-view="list"><i class="fas fa-list"></i></button>
            </div>
            <div class="products-grid" id="productsGrid">
                @foreach($products as $product)
                <div class="product-card" data-id="{{ $product['id'] }}" data-name="{{ $product['name'] }}" data-price="{{ $product['price'] }}" data-category="{{ $product['category'] }}" data-is-fuel="{{ $product['is_fuel'] ?? false ? 'true' : 'false' }}" data-fuel-price="{{ $product['fuel_price'] ?? '' }}" id="product-{{ $product['id'] }}">
                    <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" class="product-image">
                    <div class="product-info">
                        <h3 class="product-name">{{ $product['name'] }}</h3>
                        <p class="product-code">{{ $product['code'] }}</p>
                        <p class="product-price">S/ {{ number_format($product['price'], 2) }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </section>

        <aside class="cart-section">
            <div class="cart-container">
                <h2 class="cart-title">Carrito de Compras</h2>
                <div class="gas-panel-cart" id="gasPanelCart" style="display: none;">
                    <div class="gas-panel-title"><i class="fas fa-gas-pump"></i> <span id="gasProductName">Combustible</span> <i class="fas fa-times gas-panel-close" id="gasPanelClose" title="Quitar combustible"></i></div>
                    <input type="hidden" id="activeFuelId" value="">
                    <div class="gas-panel-row">
                        <button type="button" class="gas-type-btn active" id="gasByAmount">MONTO (S/)</button>
                        <button type="button" class="gas-type-btn" id="gasByGallons">GALONES</button>
                    </div>
                    <input type="number" id="gasInputAmount" class="gas-input-field" placeholder="0.00" value="0" min="0" step="0.01">
                    <div class="gas-info">
                        <div class="gas-info-row"><span>Precio galón:</span><span id="gasPriceDisplay">S/ 0.00</span></div>
                        <div class="gas-info-row"><span>Galones:</span><span id="gasGallonsDisplay">0.00</span></div>
                        <div class="gas-info-row total"><span>TOTAL:</span><span id="gasTotalDisplay">S/ 0.00</span></div>
                    </div>
                </div>
                <div class="cart-empty" id="cartEmpty">
                    <i class="fas fa-shopping-cart cart-empty-icon"></i>
                    <p>No has agregado productos</p>
                    <span>no tienes productos agregados</span>
                </div>
                <div class="cart-items" id="cartItems"></div>
                <div class="cart-summary" id="cartSummary" style="display: none;">
                    <div class="summary-row"><span>Total Productos:</span><span id="totalProducts">0</span></div>
                    <div class="summary-row total"><span>Total:</span><span id="totalAmount">S/ 0.00</span></div>
                    <button class="btn-checkout" id="btnCheckout"><i class="fas fa-credit-card"></i> Finalizar Venta</button>
                </div>
            </div>
        </aside>
    </main>

    <!-- Payment Modal -->
    <div class="modal-overlay" id="paymentModal">
        <div class="modal-content payment-modal">
            <button class="modal-close" id="closeModal"><i class="fas fa-times"></i></button>
            <h2 class="modal-title">Comprobante de pago</h2>
            <div class="payment-container">
                <div class="payment-form" id="paymentForm">
                    <div class="document-tabs">
                        <button class="doc-tab active" data-type="nv">N. Venta</button>
                        <button class="doc-tab" data-type="boleta">Boleta</button>
                        <button class="doc-tab" data-type="factura">Factura</button>
                        <button class="doc-tab" data-type="vale">Vale</button>
                    </div>
                    <div class="form-row"><label>Serie</label>
                        <select id="serieSelect" class="form-input"></select>
                    </div>
                    <div class="form-row"><label>Cliente</label>
                        <div class="customer-combobox-wrapper">
                            <div class="combobox-input-wrapper">
                                <input type="text" id="customerSearch" class="form-input combobox-input" placeholder="Clientes - Varios - 00000000" autocomplete="off">
                                <button type="button" class="combobox-toggle" id="customerToggle"><i class="fas fa-chevron-down"></i></button>
                            </div>
                            <div class="combobox-dropdown" id="customerDropdown">
                                <div class="combobox-options" id="customerOptions">
                                    <div class="combobox-option selected" data-id="1" data-name="Clientes - Varios" data-doc="00000000">Clientes - Varios - 00000000</div>
                                    @foreach($customers as $customer)
                                    @if($customer['id'] != 1)
                                    <div class="combobox-option" data-id="{{ $customer['id'] }}" data-name="{{ $customer['name'] }}" data-doc="{{ $customer['number'] }}">{{ $customer['name'] }} - {{ $customer['number'] }}</div>
                                    @endif
                                    @endforeach
                                </div>
                            </div>
                            <select id="customerSelect" class="form-input" style="display:none;">
                                <option value="1" selected>Clientes - Varios - 00000000</option>
                                @foreach($customers as $customer)
                                @if($customer['id'] != 1)
                                <option value="{{ $customer['id'] }}">{{ $customer['name'] }} - {{ $customer['number'] }}</option>
                                @endif
                                @endforeach
                            </select>
                            <button class="btn-add" type="button" id="btnAddCustomer"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="amount-display">
                        <p class="amount-label">Monto a cobrar</p>
                        <p class="amount-value" id="amountToPay">S/ 0.00</p>
                        <p class="amount-change">Vuelto: <span>S/ 0.00</span></p>
                    </div>
                    <div class="quick-amounts">
                        <button class="quick-btn" data-amount="10">S/10</button>
                        <button class="quick-btn" data-amount="20">S/20</button>
                        <button class="quick-btn" data-amount="50">S/50</button>
                        <button class="quick-btn" data-amount="100">S/100</button>
                        <button class="quick-btn" data-amount="exact">Exacto</button>
                    </div>
                    <div class="payments-list">
                        <div class="payments-header"><span>Pagos agregados:</span><button class="btn-add-payment" id="btnAddPayment"><i class="fas fa-plus"></i> Pagos</button></div>
                        <div class="payments-items" id="paymentsContainer"></div>
                        <div class="payments-summary">
                            <span>Total pagado: <strong id="totalPaidDisplay">S/ 0.00</strong></span>
                            <span class="payments-change-sep">| Vuelto: <strong id="changeAmount">S/ 0.00</strong></span>
                        </div>
                    </div>
                    <div id="voucherSection" style="display: none;">
                        <div class="form-row"><label>Doc. Cliente</label>
                            <input type="text" id="voucherCustomerDoc" class="form-input" placeholder="DNI/RUC del cliente">
                        </div>
                        <div class="form-row"><label>Saldo Disponible</label>
                            <input type="text" id="voucherBalance" class="form-input" readonly value="S/ 0.00">
                        </div>
                        <button type="button" class="btn-check-voucher" id="btnCheckVoucher"><i class="fas fa-search"></i> Consultar Saldo</button>
                    </div>
                    <div class="form-row"><label>N° Placa</label><input type="text" id="plateInput" class="form-input" placeholder="Ingrese número de placa" style="text-transform: uppercase;"></div>
                    <button class="btn-finalize" id="btnFinalize"><i class="fas fa-check"></i> Finalizar Venta</button>
                </div>
                <div class="payment-success" id="paymentSuccess" style="display: none;">
                    <div class="success-icon"><i class="fas fa-check-circle"></i></div>
                    <h3>Venta realizada con éxito</h3>
                    <button class="btn-new-sale" id="btnNewSale"><i class="fas fa-plus"></i> Nueva Venta</button>
                    <div class="pdf-preview-section">
                        <h4>Pagos agregados</h4>
                        <button class="btn-reprint"><i class="fas fa-print"></i> Volver a imprimir</button>
                        <button class="btn-view-pdf" id="btnViewPdf"><i class="fas fa-file-pdf"></i> Ver formato PDF</button>
                    </div>
                </div>
                <div class="pdf-preview-container" id="pdfPreview" style="display: none;">
                    <div class="pdf-toolbar">
                        <button class="pdf-btn" id="btnDownloadPdf" title="Descargar"><i class="fas fa-download"></i></button>
                        <button class="pdf-btn" id="btnPrintPdf" title="Imprimir"><i class="fas fa-print"></i></button>
                    </div>
                    <div class="pdf-viewer" id="pdfViewer">
                        <div class="pdf-scroll-container">
                            <div class="thermal-paper" id="thermalPaper">
                                <div class="thermal-ticket" id="thermalTicket">
                                    <div class="thermal-header">
                                        <img src="{{ $logo }}" alt="FactReady Lite" style="max-width: 80px; height: auto; margin-bottom: 8px;">
                                        <div>RUC: 44444444444</div>
                                        <div>CALLE DE PRUEBA , AREQUIPA ,</div>
                                        <div>AREQUIPA - AREQUIPA</div>
                                        <div>factreadylite@gmail.com</div>
                                        <div>&nbsp;</div>
                                        <div>9999999999</div>
                                    </div>
                                    <div class="thermal-doc-title">NOTA DE VENTA</div>
                                    <div class="thermal-doc-num">NV01-00000001</div>
                                    <div class="thermal-divider"></div>
                                    <div>F. Emisión: 2026-03-26 / 22:55:47</div>
                                    <div>Cliente: Clientes - Varios</div>
                                    <div>Doc.trib.no.dom.sin.ruc: 99999999</div>
                                    <div>Dirección: , , -</div>
                                    <div>Vendedor: {{ $user['name'] }}</div>
                                    <div class="thermal-divider"></div>
                                    <div class="thermal-table-header">CANT.  UNIDAD  DESCRIPCIÓN  P.UNIT  TOTAL</div>
                                    <div class="thermal-divider"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button class="btn-back-form" id="btnBackForm"><i class="fas fa-arrow-left"></i> Volver</button>
                </div>
                <div class="receipt-preview" id="receiptPreview">
                    <div class="receipt-preview-card">
                        <div class="preview-receipt">
                            <div class="preview-header">
                                <img src="{{ $logo }}" alt="FactReady Lite" style="max-width: 120px; height: auto; margin-bottom: 8px;">
                                <p class="preview-company">RUC 44444444444</p>
                                <p class="preview-company">CALLE DE PRUEBA , AREQUIPA , AREQUIPA -</p>
                                <p class="preview-company">AREQUIPA</p>
                                <p class="preview-company">Central telefonica: 9999999999</p>
                                <p class="preview-company">Email: factreadylite@gmail.com</p>
                            </div>
                            
                            <h3 class="preview-title" id="previewTitle">NOTA DE VENTA</h3>
                            
                            <div class="preview-info">
                                <div class="preview-info-row">
                                    <span class="info-label">F. Emisión:</span>
                                    <span class="info-value" id="previewFecha"></span>
                                </div>
                                <div class="preview-info-row">
                                    <span class="info-label">H. Emisión:</span>
                                    <span class="info-value" id="previewHora"></span>
                                </div>
                                <div class="preview-info-row">
                                    <span class="info-label">Cliente:</span>
                                    <span class="info-value" id="previewCliente"></span>
                                </div>
                                <div class="preview-info-row">
                                    <span class="info-label" id="previewDocLabel">DNI/RUC:</span>
                                    <span class="info-value" id="previewDoc"></span>
                                </div>
                                <div class="preview-info-row">
                                    <span class="info-label">Vendedor:</span>
                                    <span class="info-value">{{ $user['name'] }}</span>
                                </div>
                                <div class="preview-info-row" id="previewPlateRow" style="display: none;">
                                    <span class="info-label">N° Placa:</span>
                                    <span class="info-value" id="previewPlate"></span>
                                </div>
                            </div>
                            
                            <table class="preview-table">
                                <thead>
                                    <tr>
                                        <th class="col-cant">CANT</th>
                                        <th class="col-unid">UNID</th>
                                        <th class="col-desc">DESCRIPCIÓN</th>
                                        <th class="col-price">P.UNIT</th>
                                        <th class="col-total">TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody id="previewItems">
                                    <tr class="empty-row">
                                        <td colspan="5">Productos aparecerán aquí</td>
                                    </tr>
                                </tbody>
                            </table>
                            
                            <div class="preview-totals">
                                <div class="preview-total-line"><span>OP. GRAVADAS:</span><span id="previewSubtotal">S/ 0.00</span></div>
                                <div class="preview-total-line"><span>IGV:</span><span id="previewIgv">S/ 0.00</span></div>
                                <div class="preview-total-line total"><span>TOTAL A PAGAR:</span><span id="previewTotal">S/ 0.00</span></div>
                            </div>
                            
                            <div class="preview-son">Son: <span id="previewSon"></span></div>
                            
                            <div class="preview-footer">
                                <div class="preview-footer-row">
                                    <span>CONDICIÓN DE PAGO:</span>
                                    <span>Contado</span>
                                </div>
                                <div class="preview-footer-row">
                                    <span>PAGOS:</span>
                                    <span>Efectivo - S/ <span id="previewPaid">0.00</span></span>
                                </div>
                                <div class="preview-footer-row">
                                    <span>SALDO:</span>
                                    <span id="previewSaldo">S/ 0.00</span>
                                </div>
                                <div class="preview-footer-row" id="previewVoucherBalanceRow" style="display: none; background: #d4edda; padding: 4px; border-radius: 4px;">
                                    <span><i class="fas fa-ticket-alt"></i> SALDO VALE:</span>
                                    <span id="previewVoucherBalance" style="font-weight: bold; color: #155724;">S/ 0.00</span>
                                </div>
                            </div>
                            
                            <div class="preview-link">Para consultar el comprobante ingresa a https://{{ $apiDomain }}/buscar</div>
                        </div>
                    </div>
                </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="welcome-toast" id="welcomeToast"><i class="fas fa-check-circle"></i><span>Bienvenido, {{ $user['name'] }}</span></div>
    
    <!-- Cash Opening Modal -->
    <div class="modal-overlay" id="cashModal">
        <div class="modal-content cash-modal">
            <div class="cash-modal-header">
                <i class="fas fa-cash-register"></i>
                <h2>Apertura de Caja</h2>
            </div>
            <div class="cash-modal-body">
                <p class="cash-modal-text">Debes abrir la caja para comenzar a vender</p>
                <div class="cash-input-group">
                    <label for="initialAmount">Monto inicial</label>
                    <div class="cash-input-wrapper">
                        <span class="cash-prefix">S/</span>
                        <input type="number" id="initialAmount" class="cash-input" value="0" min="0" step="0.01" placeholder="0.00">
                    </div>
                </div>
                <button class="btn-open-cash" id="btnOpenCash">
                    <i class="fas fa-lock-open"></i>
                    Abrir Caja
                </button>
            </div>
        </div>
    </div>

    <!-- Cash Detail Modal -->
    <div class="modal-overlay" id="cashDetailModal">
        <div class="modal-content cash-detail-modal">
            <button class="modal-close cash-detail-close"><i class="fas fa-times"></i></button>
            <div class="cash-detail-header">
                <i class="fas fa-cash-register"></i>
                <h2>Detalles de Caja</h2>
            </div>
            <div class="cash-detail-body" id="cashDetailBody">
                <div class="cash-detail-loading">
                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                </div>
            </div>
        </div>
    </div>

    <!-- New Customer Modal -->
    <div class="modal-overlay" id="newCustomerModal">
        <div class="modal-content customer-modal">
            <button class="modal-close" id="closeCustomerModal"><i class="fas fa-times"></i></button>
            <h2 class="modal-title">Nuevo Cliente</h2>
            <div class="customer-form">
                <div class="form-row">
                    <label>Tipo Doc. Identidad *</label>
                    <select id="customerDocType" class="form-input">
                        <option value="1">DNI</option>
                        <option value="6">RUC</option>
                        <option value="0">Doc.trib.no.dom.sin.ruc</option>
                    </select>
                </div>
                <div class="form-row">
                    <label>Número *</label>
                    <div class="input-search-group">
                        <input type="text" id="customerDocNumber" class="form-input" placeholder="Ingrese número" maxlength="11">
                        <button type="button" id="btnSearchDoc" class="btn-search-doc" title="Buscar en apiperu.dev">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <div id="searchDocFeedback" class="search-feedback"></div>
                </div>
                <div class="form-row">
                    <label>Nombre *</label>
                    <input type="text" id="customerName" class="form-input" placeholder="Ingrese nombre">
                </div>
                <div class="form-row">
                    <label>Nombre comercial</label>
                    <input type="text" id="customerTradeName" class="form-input" placeholder="Nombre comercial (opcional)">
                </div>
                <div class="form-row">
                    <label>Dirección</label>
                    <input type="text" id="customerAddress" class="form-input" placeholder="Av. Principal">
                </div>
                <div class="form-row">
                    <label>Teléfono</label>
                    <input type="text" id="customerPhone" class="form-input" placeholder="Teléfono (opcional)">
                </div>
                <div class="form-row">
                    <label>Correo electrónico</label>
                    <input type="email" id="customerEmail" class="form-input" placeholder="Correo (opcional)">
                </div>
                <button class="btn-finalize" id="btnSaveCustomer"><i class="fas fa-save"></i> Guardar Cliente</button>
            </div>
        </div>
    </div>
</div>

<style>
:root { --primary: #1f7a83; --accent: #ff7a00; --accent-hover: #ff8c1a; --success: #22c55e; --bg-main: #0f2027; --bg-secondary: #1c2b2f; --bg-card: #243a3f; --text-primary: #ffffff; --text-secondary: #a0b3b8; --border-color: #2d4a50; --shadow: 0 10px 40px rgba(0,0,0,0.3); }
[data-theme="light"] { --bg-main: #f5f5f5; --bg-secondary: #ffffff; --bg-card: #ffffff; --text-primary: #0f4c5c; --text-secondary: #7a8a91; --border-color: #e5e7eb; --shadow: 0 10px 40px rgba(0,0,0,0.1); }
* { margin: 0; padding: 0; box-sizing: border-box; } body.pos-page { font-family: 'Inter', sans-serif; background: var(--bg-main); color: var(--text-primary); min-height: 100vh; }
.pos-container { display: flex; flex-direction: column; height: 100vh; }
.pos-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: var(--bg-secondary); box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.header-left { display: flex; align-items: center; gap: 12px; }
.logo { display: flex; align-items: center; gap: 10px; font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 20px; }
.logo i { color: var(--accent); font-size: 24px; }
.header-right { display: flex; align-items: center; gap: 12px; }
.header-btn { width: 40px; height: 40px; border-radius: 10px; border: none; background: var(--bg-card); color: var(--text-secondary); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s; }
.header-btn:hover { background: var(--primary); color: white; }
.user-info { display: flex; align-items: center; gap: 12px; padding: 8px 16px; background: var(--bg-card); border-radius: 10px; }
.user-info span { font-weight: 500; }
.logout-btn { color: var(--text-secondary); text-decoration: none; } .logout-btn:hover { color: var(--accent); }
.pos-main { display: flex; flex: 1; overflow: hidden; }
.products-section { flex: 1; padding: 24px; overflow-y: auto; }
.category-filters { display: flex; gap: 12px; margin-bottom: 20px; }
.category-select { flex: 1; padding: 12px 16px; border: 2px solid var(--border-color); border-radius: 12px; background: var(--bg-card); color: var(--text-primary); font-size: 14px; cursor: pointer; }
.category-select:focus { outline: none; border-color: var(--accent); }
.category-search-wrapper { position: relative; flex: 1; }
.category-search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); }
.category-search-input { width: 100%; padding: 12px 16px 12px 40px; border: 2px solid var(--border-color); border-radius: 12px; background: var(--bg-card); color: var(--text-primary); font-size: 14px; }
.category-search-input:focus { outline: none; border-color: var(--accent); }
.category-search-input::placeholder { color: var(--text-secondary); }
.category-buttons { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 12px; }
.category-btn { padding: 10px 20px; border: 2px solid var(--border-color); border-radius: 25px; background: transparent; color: var(--text-secondary); font-weight: 500; cursor: pointer; transition: all 0.3s; }
.category-btn.hidden { display: none; }
.category-btn.active, .category-btn:hover { background: var(--accent); border-color: var(--accent); color: white; }
.search-bar { position: relative; display: flex; align-items: center; background: var(--bg-card); border-radius: 12px; padding: 12px 16px; margin-bottom: 20px; border: 2px solid var(--border-color); }
.search-icon { color: var(--text-secondary); margin-right: 12px; }
.search-bar input { flex: 1; border: none; background: transparent; color: var(--text-primary); font-size: 15px; }
.search-bar input:focus { outline: none; } .search-bar input::placeholder { color: var(--text-secondary); }
.search-results { position: absolute; top: 100%; left: 16px; right: 56px; background: var(--bg-card); border: 2px solid var(--border-color); border-top: none; border-radius: 0 0 12px 12px; max-height: 300px; overflow-y: auto; z-index: 100; display: none; }
.search-result-item { padding: 12px 16px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); }
.search-result-item:last-child { border-bottom: none; }
.search-result-item:hover { background: var(--accent); color: white; }
.search-result-name { font-weight: 500; }
.search-result-price { color: var(--accent); font-weight: 600; }
.search-result-item:hover .search-result-price { color: white; }
.scan-icon { color: var(--text-secondary); margin-left: 12px; cursor: pointer; }
.view-toggle { display: flex; gap: 8px; margin-bottom: 20px; }
.view-btn { width: 40px; height: 40px; border-radius: 10px; border: 2px solid var(--border-color); background: transparent; color: var(--text-secondary); cursor: pointer; transition: all 0.3s; }
.view-btn.active, .view-btn:hover { background: var(--primary); border-color: var(--primary); color: white; }
.products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px; }
.products-grid.list-view { display: flex; flex-direction: column; gap: 8px; }
.product-card { background: var(--bg-card); border-radius: 14px; padding: 16px; cursor: pointer; transition: all 0.3s; border: 2px solid transparent; }
.product-card.list-card { display: flex; align-items: center; padding: 12px 16px; }
.product-card.list-card .product-image { width: 60px; height: 60px; margin-bottom: 0; margin-right: 16px; }
.product-card.list-card .product-info { flex: 1; display: flex; align-items: center; justify-content: space-between; }
.product-card.list-card .product-name { margin-bottom: 0; }

.product-card.list-card .product-code { margin-bottom: 0; margin-right: 16px; }
.product-card.list-card .product-price { margin-bottom: 0; }
.product-card:hover { transform: translateY(-4px); box-shadow: var(--shadow); border-color: var(--accent); }
.product-image { width: 100%; height: 120px; object-fit: cover; border-radius: 10px; margin-bottom: 12px; }
.product-name { font-size: 14px; font-weight: 600; margin-bottom: 4px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.product-code { font-size: 12px; color: var(--text-secondary); margin-bottom: 8px; }
.product-price { font-size: 18px; font-weight: 700; color: var(--accent); }
.cart-section { width: 350px; background: var(--bg-secondary); border-left: 1px solid var(--border-color); display: flex; flex-direction: column; }
.cart-container { padding: 20px; display: flex; flex-direction: column; height: 100%; }
.cart-title { font-family: 'Montserrat', sans-serif; font-size: 18px; font-weight: 700; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid var(--border-color); }
.cart-empty { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
.gas-panel-cart { background: linear-gradient(135deg, #ff6b35, #f7931e); border-radius: 12px; padding: 15px; margin-bottom: 16px; color: white; }
.gas-panel-title { font-weight: 700; font-size: 14px; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; }
.gas-panel-title i { margin-right: 6px; }
.gas-panel-close { cursor: pointer; font-size: 14px; opacity: 0.8; transition: opacity 0.2s; }
.gas-panel-close:hover { opacity: 1; }
.gas-panel-row { display: flex; gap: 8px; margin-bottom: 10px; }
.gas-type-btn { flex: 1; padding: 8px; border: none; border-radius: 6px; background: rgba(255,255,255,0.2); color: white; font-size: 11px; font-weight: 600; cursor: pointer; }
.gas-type-btn.active { background: white; color: #ff6b35; }
.gas-input-field { width: 100%; padding: 10px; border: none; border-radius: 6px; font-size: 20px; font-weight: 700; text-align: center; margin-bottom: 10px; }
.gas-info { background: rgba(0,0,0,0.2); border-radius: 6px; padding: 10px; }
.gas-info-row { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px; }
.gas-info-row.total { font-size: 16px; font-weight: 700; margin-top: 6px; padding-top: 6px; border-top: 1px solid rgba(255,255,255,0.3); }
.cart-empty-icon { font-size: 64px; color: var(--success); margin-bottom: 16px; }
.cart-empty p { font-size: 16px; font-weight: 600; margin-bottom: 4px; } .cart-empty span { font-size: 14px; color: var(--text-secondary); }
.cart-items { flex: 1; overflow-y: auto; margin-bottom: 16px; }
.cart-item { display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--bg-card); border-radius: 10px; margin-bottom: 10px; }
.cart-item-info { flex: 1; } .cart-item-name { font-size: 14px; font-weight: 600; margin-bottom: 4px; }
.cart-item-price { font-size: 12px; color: var(--text-secondary); }
.cart-item-controls { display: flex; align-items: center; gap: 8px; }
.qty-btn { width: 28px; height: 28px; border-radius: 6px; border: none; background: var(--bg-main); color: var(--text-primary); cursor: pointer; display: flex; align-items: center; justify-content: center; }
.qty-btn:hover { background: var(--primary); } .qty-value, .qty-input { min-width: 32px; width: 50px; text-align: center; font-weight: 600; background: transparent; border: none; color: var(--text-primary); font-size: 14px; }
.qty-input { border-radius: 4px; padding: 4px; background: var(--bg-main); }
.qty-input:focus { outline: 2px solid var(--primary); }
.cart-item-total { font-weight: 700; color: var(--accent); min-width: 60px; text-align: right; }
.cart-item-delete { color: var(--text-secondary); cursor: pointer; margin-left: 8px; } .cart-item-delete:hover { color: #ef4444; }
.cart-summary { border-top: 2px solid var(--border-color); padding-top: 16px; }
.summary-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
.summary-row.total { font-size: 18px; font-weight: 700; margin-bottom: 16px; color: var(--accent); }
.btn-checkout { width: 100%; padding: 16px; background: var(--accent); color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s; }
.btn-checkout:hover { background: var(--accent-hover); transform: translateY(-2px); }
.btn-check-voucher { width: 100%; padding: 10px; background: var(--primary); color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 12px; transition: all 0.3s; }
.btn-check-voucher:hover { background: #2a9aa8; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: none; align-items: center; justify-content: center; z-index: 1000; padding: 20px; }
.modal-overlay.active { display: flex; }
.modal-content { background: var(--bg-secondary); border-radius: 20px; max-width: 1000px; width: 100%; max-height: 90vh; overflow: hidden; position: relative; }
.modal-close { position: absolute; top: 16px; right: 16px; width: 40px; height: 40px; border-radius: 10px; border: none; background: var(--bg-card); color: var(--text-secondary); cursor: pointer; z-index: 10; }
.modal-close:hover { background: #ef4444; color: white; }
.modal-title { padding: 20px 24px; font-family: 'Montserrat', sans-serif; font-size: 20px; font-weight: 700; border-bottom: 1px solid var(--border-color); }
.payment-modal { max-height: 85vh; }
.payment-container { display: flex; height: calc(85vh - 80px); }
.payment-form, .payment-success { flex: 1; padding: 24px; overflow-y: auto; }
.document-tabs { display: flex; gap: 8px; margin-bottom: 24px; }
.doc-tab { flex: 1; padding: 12px; border: 2px solid var(--border-color); border-radius: 10px; background: transparent; color: var(--text-secondary); font-weight: 600; cursor: pointer; transition: all 0.3s; }
.doc-tab.active { background: var(--accent); border-color: var(--accent); color: white; }
.form-row { margin-bottom: 16px; } .form-row label { display: block; font-size: 13px; font-weight: 500; color: var(--text-secondary); margin-bottom: 6px; }
.form-input { width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: 10px; background: var(--bg-card); color: var(--text-primary); font-size: 14px; }
.form-input:focus { outline: none; border-color: var(--primary); }
.input-with-btn { display: flex; gap: 8px; } .input-with-btn .form-input { flex: 1; }
.customer-combobox-wrapper { position: relative; display: flex; gap: 8px; flex: 1; }
.combobox-input-wrapper { position: relative; flex: 1; }
.combobox-input { padding-right: 40px; cursor: pointer; }
.combobox-toggle { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 12px; pointer-events: none; }
.combobox-dropdown { display: none; position: absolute; top: 100%; left: 0; right: 0; max-height: 250px; overflow-y: auto; background: var(--bg-card); border: 2px solid var(--primary); border-radius: 10px; margin-top: 4px; z-index: 1000; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
.combobox-dropdown.show { display: block; }
.combobox-option { padding: 10px 12px; cursor: pointer; transition: background 0.2s; border-bottom: 1px solid var(--border-color); font-size: 13px; }
.combobox-option:last-child { border-bottom: none; }
.combobox-option:hover, .combobox-option.highlighted { background: var(--primary); color: white; }
.combobox-option.selected { background: var(--primary); color: white; }
.combobox-option .doc-badge { font-size: 12px; opacity: 0.8; margin-left: 8px; }
.customer-combobox-wrapper .btn-add { flex-shrink: 0; }
.btn-add { width: 44px; height: 44px; border-radius: 10px; border: none; background: var(--primary); color: white; cursor: pointer; }
.amount-display { text-align: center; padding: 24px; background: var(--bg-card); border-radius: 14px; margin-bottom: 20px; }
.amount-label { font-size: 14px; color: var(--text-secondary); margin-bottom: 8px; }
.amount-value { font-size: 36px; font-weight: 700; color: var(--accent); margin-bottom: 4px; } .amount-change { font-size: 14px; color: var(--text-secondary); }
.money-input-wrapper { position: relative; display: flex; align-items: center; }
.money-prefix { position: absolute; left: 16px; font-weight: 600; color: var(--text-secondary); }
.money-input { padding-left: 40px; font-size: 18px; font-weight: 600; }
.quick-amounts { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
.quick-btn { flex: 1; min-width: 60px; padding: 10px; border: 2px solid var(--border-color); border-radius: 8px; background: transparent; color: var(--text-primary); font-weight: 600; cursor: pointer; transition: all 0.3s; }
.quick-btn:hover { background: var(--primary); border-color: var(--primary); color: white; }
.payments-list { background: var(--bg-card); border-radius: 12px; padding: 16px; margin-bottom: 16px; }
.payments-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; font-size: 14px; font-weight: 500; }
.btn-add-payment { padding: 6px 12px; border-radius: 6px; border: none; background: var(--primary); color: white; font-size: 12px; cursor: pointer; }
.payment-item { display: flex; justify-content: space-between; padding: 10px; background: var(--bg-main); border-radius: 8px; margin-bottom: 8px; font-size: 14px; }
.payment-row { display: flex; gap: 8px; align-items: center; margin-bottom: 10px; }
.payment-row .payment-num { min-width: 24px; font-weight: 600; font-size: 13px; color: var(--text-secondary); }
.payment-row select { flex: 2; padding: 8px 10px; border: 2px solid var(--border-color); border-radius: 8px; background: var(--bg-card); color: var(--text-primary); font-size: 13px; }
.payment-row select:focus { outline: none; border-color: var(--primary); }
.payment-row .payment-amount-wrap { flex: 1; position: relative; display: flex; align-items: center; }
.payment-row .payment-amount-wrap .money-prefix-sm { position: absolute; left: 10px; font-size: 12px; font-weight: 600; color: var(--text-secondary); }
.payment-row .payment-amount-wrap input { width: 100%; padding: 8px 10px 8px 28px; border: 2px solid var(--border-color); border-radius: 8px; background: var(--bg-card); color: var(--text-primary); font-size: 14px; font-weight: 600; }
.payment-row .payment-amount-wrap input:focus { outline: none; border-color: var(--primary); }
.payment-row .payment-del-btn { width: 32px; height: 32px; border-radius: 6px; border: none; background: transparent; color: var(--text-secondary); cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; transition: all 0.2s; flex-shrink: 0; }
.payment-row .payment-del-btn:hover { background: #ef4444; color: white; }
.payments-summary { display: flex; justify-content: space-between; align-items: center; padding: 10px 0 0; margin-top: 8px; border-top: 1px solid var(--border-color); font-size: 14px; }
.payments-summary strong { color: var(--accent); }
.payments-change-sep { color: var(--text-secondary); }
.btn-finalize { width: 100%; padding: 16px; background: var(--accent); color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; }
.btn-finalize:hover { background: var(--accent-hover); }
.payment-success { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
.success-icon { width: 100px; height: 100px; background: var(--success); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 24px; }
.success-icon i { font-size: 50px; color: white; }
.payment-success h3 { font-size: 24px; margin-bottom: 24px; }
.btn-new-sale { padding: 14px 32px; background: var(--primary); color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; margin-bottom: 32px; }
.pdf-preview-section { width: 100%; padding-top: 24px; border-top: 1px solid var(--border-color); }
.pdf-preview-section h4 { margin-bottom: 16px; }
.pdf-preview-section button { display: block; width: 100%; padding: 12px; margin-bottom: 10px; border-radius: 10px; border: 2px solid var(--border-color); background: transparent; color: var(--text-primary); font-weight: 500; cursor: pointer; transition: all 0.3s; }
.pdf-preview-section button:hover { background: var(--primary); border-color: var(--primary); }
.pdf-preview-container { flex: 1; display: flex; flex-direction: column; background: #121212; padding: 24px; overflow: hidden; }
.pdf-toolbar { display: flex; gap: 12px; margin-bottom: 20px; padding: 0 4px; flex-shrink: 0; }
.pdf-btn { width: 44px; height: 44px; border-radius: 10px; border: none; background: #2a2a2a; color: #888; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.pdf-btn:hover { background: var(--primary); color: white; }
.pdf-viewer { flex: 1; display: flex; justify-content: center; align-items: center; overflow: hidden; min-height: 0; }
.pdf-scroll-container { width: 100%; height: 100%; overflow-y: auto; display: flex; justify-content: center; padding: 20px; }
.thermal-paper { background: #fff; border-radius: 4px; box-shadow: 0 8px 32px rgba(0,0,0,0.6); margin: auto; display: block; max-width: 320px; width: 100%; }
.thermal-ticket { font-family: 'Courier New', Courier, monospace; font-size: 11px; line-height: 1.4; color: #000; margin: 0; padding: 16px 12px; }
.thermal-header { text-align: center; margin-bottom: 8px; }
.thermal-brand { font-weight: bold; font-size: 14px; margin-bottom: 4px; }
.thermal-doc-title { text-align: center; font-weight: bold; font-size: 12px; margin: 8px 0 4px; }
.thermal-doc-num { text-align: center; font-size: 11px; margin-bottom: 8px; }
.thermal-divider { border-top: 1px dashed #000; margin: 8px 0; }
.thermal-table-header { font-size: 10px; font-weight: bold; }
.thermal-table { border-collapse: collapse; width: 100%; }
.thermal-table td { padding: 2px 0; font-size: 10px; }
.thermal-table tr:first-child td { font-weight: bold; }
.thermal-link { font-size: 9px; word-break: break-all; margin-top: 12px; text-align: center; }
.thermal-item { display: flex; justify-content: space-between; font-size: 10px; }
.thermal-total { font-weight: bold; font-size: 12px; margin-top: 8px; text-align: right; }
.thermal-payment { font-size: 10px; margin-top: 8px; }
.btn-back-form { margin-top: 20px; padding: 12px 24px; border-radius: 8px; border: none; background: var(--primary); color: white; cursor: pointer; align-self: flex-start; transition: all 0.3s; }
.btn-back-form:hover { background: #2a9aa8; }
.receipt-preview { width: 420px; background: #e5e7eb; padding: 12px; border-left: 1px solid var(--border-color); overflow-y: auto; }
.receipt-preview-card { background: white; border-radius: 16px; padding: 0; box-shadow: 0 8px 32px rgba(0,0,0,0.12); overflow: hidden; }
.preview-receipt { color: #333; }
.preview-header { text-align: center; padding: 10px 12px 8px; background: #fafafa; border-bottom: 1px solid #eee; }
.preview-company { font-size: 9px; color: #555; margin: 2px 0; line-height: 1.4; }
.preview-title { text-align: center; font-size: 11px; font-weight: 700; padding: 8px 12px; margin: 0; background: #f0f0f0; color: #222; }
.preview-info { padding: 8px 12px; border-bottom: 1px dashed #ccc; }
.preview-info-row { display: flex; justify-content: space-between; margin-bottom: 2px; font-size: 8px; line-height: 1.4; }
.preview-info-row:last-child { margin-bottom: 0; }
.info-label { color: #666; }
.info-value { color: #333; font-weight: 500; }
.preview-table { width: 100%; border-collapse: collapse; font-size: 9px; margin: 0 2px; table-layout: fixed; }
.preview-table th { background: #f5f5f5; padding: 1px; text-align: left; font-weight: 600; color: #444; border-bottom: 1px solid #ddd; text-transform: uppercase; font-size: 7px; }
.preview-table td { padding: 1px; border-bottom: 1px solid #eee; vertical-align: middle; font-size: 9px; }
.preview-table .col-cant { width: 8%; }
.preview-table .col-unid { width: 8%; }
.preview-table .col-desc { width: 46%; }
.preview-table .col-price { width: 19%; }
.preview-table .col-total { width: 19%; }
.preview-table .empty-row td { padding: 10px 1px; text-align: center; color: #999; font-style: italic; }
.preview-totals { padding: 12px 20px; border-top: 1px dashed #ccc; }
.preview-total-line { display: flex; justify-content: space-between; font-size: 10px; margin-bottom: 4px; }
.preview-total-line.total { font-size: 12px; font-weight: 700; color: #222; margin-top: 8px; padding-top: 8px; border-top: 1px solid #333; }
.preview-son { padding: 8px 20px; font-size: 9px; font-style: italic; color: #666; border-top: 1px dashed #ccc; }
.preview-footer { padding: 12px 20px; border-top: 1px dashed #ccc; }
.preview-footer-row { display: flex; justify-content: space-between; font-size: 10px; margin-bottom: 4px; color: #555; }
.preview-footer-row:last-child { margin-bottom: 0; }
.preview-footer-row span:first-child { font-weight: 500; }
.preview-footer-row span:last-child { color: #333; }
.preview-link { padding: 12px 20px; font-size: 8px; text-align: center; color: #666; border-top: 1px dashed #ccc; }
.doc-header { text-align: center; margin-bottom: 8px; }
.doc-company-name { font-weight: bold; font-size: 12px; margin-bottom: 2px; }
.doc-company-ruc { font-size: 10px; margin-bottom: 2px; }
.doc-company-address { font-size: 9px; margin-bottom: 1px; }
.doc-company-country { font-size: 9px; margin-bottom: 1px; }
.doc-company-email { font-size: 9px; margin-bottom: 1px; }
.doc-company-phone { font-size: 9px; margin-bottom: 2px; }
.doc-title { text-align: center; font-weight: bold; font-size: 11px; margin: 8px 0 4px; }
.doc-number { text-align: center; font-size: 10px; margin-bottom: 8px; }
.doc-divider { border-top: 1px dashed #000; margin: 6px 0; }
.doc-info { font-size: 9px; margin-bottom: 2px; }
.doc-customer { font-size: 9px; margin-bottom: 1px; }
.doc-customer-doc { font-size: 9px; margin-bottom: 1px; }
.doc-customer-address { font-size: 9px; margin-bottom: 4px; }
.doc-items { width: 100%; border-collapse: collapse; font-size: 9px; margin: 6px 0; }
.doc-items th { font-weight: bold; text-align: left; padding: 2px; }
.doc-items td { padding: 2px; }
.doc-totals { text-align: right; font-size: 9px; }
.doc-total { font-weight: bold; font-size: 11px; margin-top: 4px; }
.doc-payment { font-size: 9px; margin-top: 6px; }
.doc-footer { font-size: 8px; text-align: center; margin-top: 10px; word-break: break-all; }

/* Preview Receipt Styles */
.preview-receipt { color: #333; }
.preview-header { text-align: center; padding: 20px 24px 16px; background: #fafafa; border-bottom: 1px solid #eee; }
.preview-company { font-size: 11px; color: #555; margin: 3px 0; line-height: 1.4; font-weight: 500; }
.preview-title { text-align: center; font-size: 14px; font-weight: 700; padding: 14px 24px; margin: 0; background: #f0f0f0; color: #222; letter-spacing: 0.5px; }
.preview-info { padding: 16px 24px; }
.preview-info-row { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 11px; line-height: 1.4; }
.preview-info-row:last-child { margin-bottom: 0; }
.info-label { color: #666; font-weight: 500; }
.info-value { color: #333; }
.preview-table { width: 100%; border-collapse: collapse; font-size: 10px; margin: 0 24px; }
.preview-table th { background: #f5f5f5; padding: 8px 6px; text-align: left; font-weight: 600; color: #444; border-bottom: 1px solid #ddd; font-size: 9px; text-transform: uppercase; }
.preview-table td { padding: 10px 6px; border-bottom: 1px solid #eee; vertical-align: middle; }
.preview-table .empty-row td { padding: 30px 6px; text-align: center; color: #999; font-style: italic; }
.preview-total-section { display: flex; justify-content: space-between; align-items: center; padding: 14px 24px; background: #f5f5f5; margin-top: 4px; border-top: 2px solid #222; }
.preview-total-label { font-size: 12px; font-weight: 700; color: #222; }
.preview-total-value { font-size: 18px; font-weight: 800; color: #f45227; }
.preview-footer { padding: 16px 24px; }
.preview-footer-row { display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 6px; color: #555; }
.preview-footer-row:last-child { margin-bottom: 0; }
.preview-footer-row span:first-child { font-weight: 500; }
.preview-footer-row span:last-child { color: #222; }

.welcome-toast { position: fixed; bottom: 24px; right: 24px; background: var(--success); color: white; padding: 16px 24px; border-radius: 12px; display: flex; align-items: center; gap: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); animation: slideIn 0.4s ease, slideOut 0.4s ease 3s forwards; z-index: 1001; }
@keyframes slideIn { from { transform: translateX(calc(100% + 24px)); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
@keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(calc(100% + 24px)); opacity: 0; } }
::-webkit-scrollbar { width: 6px; } ::-webkit-scrollbar-track { background: var(--bg-main); } ::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 3px; } ::-webkit-scrollbar-thumb:hover { background: var(--primary); }
@media (max-width: 900px) { .pos-main { flex-direction: column; } .cart-section { width: 100%; height: 50%; border-left: none; border-top: 1px solid var(--border-color); } .receipt-preview { display: none; } }

/* Settings Panel */
.settings-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); opacity: 0; visibility: hidden; transition: all 0.3s ease; z-index: 1100; }
.settings-overlay.active { opacity: 1; visibility: visible; }
.settings-panel { position: fixed; top: 0; right: 0; width: 320px; height: 100vh; background: var(--bg-secondary); transform: translateX(100%); transition: transform 0.3s ease; z-index: 1101; box-shadow: -10px 0 40px rgba(0,0,0,0.3); }
.settings-panel.active { transform: translateX(0); }
.settings-content { padding: 24px; height: 100%; display: flex; flex-direction: column; overflow-y: auto; }
.settings-close { position: absolute; top: 16px; right: 16px; width: 36px; height: 36px; border-radius: 10px; border: none; background: var(--bg-card); color: var(--text-secondary); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s; }
.settings-close:hover { background: #ef4444; color: white; }
.settings-header-section { text-align: center; padding: 10px 0 10px; border-bottom: 1px solid var(--border-color); margin-bottom: 15px; }
.settings-avatar { width: 120px; height: 120px; background: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 2px; padding: 2px; }
.settings-avatar img { width: 100%; height: 100%; object-fit: contain; }
.settings-avatar i { font-size: 32px; color: white; }
.settings-company { font-family: 'Montserrat', sans-serif; font-size: 20px; font-weight: 700; color: var(--text-primary); margin-bottom: 0; }
.settings-branch { font-size: 12px; font-weight: 600; color: var(--text-secondary); letter-spacing: 1px; }
.settings-user-info { text-align: center; padding: 16px; background: var(--bg-card); border-radius: 12px; margin-bottom: 24px; }
.settings-user-name { font-size: 16px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px; }
.settings-user-email { font-size: 13px; color: var(--text-secondary); }
.settings-options { flex: 1; margin-bottom: 24px; }
.settings-option { display: flex; align-items: center; padding: 16px; background: var(--bg-card); border-radius: 12px; margin-bottom: 10px; cursor: pointer; transition: all 0.3s; border: 2px solid transparent; }
.settings-option:hover { border-color: var(--primary); }
.settings-option.disabled { opacity: 0.5; cursor: not-allowed; }
.settings-option.disabled:hover { border-color: transparent; }
.option-icon { width: 40px; height: 40px; background: var(--primary); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 14px; }
.option-icon i { font-size: 16px; color: white; }
.option-text { flex: 1; }
.option-title { display: block; font-size: 14px; font-weight: 600; color: var(--text-primary); margin-bottom: 2px; }
.option-value { display: block; font-size: 11px; color: var(--text-secondary); }
.option-subtitle { display: block; font-size: 11px; color: var(--text-secondary); }
.btn-settings-exit { width: 100%; padding: 16px; background: var(--accent); color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s; margin-top: auto; }
.btn-settings-exit:hover { background: var(--accent-hover); transform: translateY(-2px); box-shadow: 0 10px 30px rgba(255, 122, 0, 0.4); }

/* Cash Opening Modal */
.cash-modal { max-width: 420px; }
.cash-modal-header { text-align: center; padding: 32px 24px 24px; background: linear-gradient(135deg, var(--primary), #2a9aa8); color: white; }
.cash-modal-header i { font-size: 48px; margin-bottom: 16px; display: block; }
.cash-modal-header h2 { font-family: 'Montserrat', sans-serif; font-size: 24px; font-weight: 700; margin: 0; }
.cash-modal-body { padding: 32px 24px; text-align: center; }
.cash-modal-text { font-size: 16px; color: var(--text-secondary); margin-bottom: 24px; }
.cash-input-group { text-align: left; margin-bottom: 24px; }
.cash-input-group label { display: block; font-size: 14px; font-weight: 500; color: var(--text-secondary); margin-bottom: 8px; }
.cash-input-wrapper { position: relative; display: flex; align-items: center; }
.cash-prefix { position: absolute; left: 16px; font-weight: 700; color: var(--text-secondary); font-size: 18px; }
.cash-input { width: 100%; padding: 16px 16px 16px 48px; border: 2px solid var(--border-color); border-radius: 12px; background: var(--bg-card); color: var(--text-primary); font-size: 24px; font-weight: 700; text-align: right; }
.cash-input:focus { outline: none; border-color: var(--primary); }
.btn-open-cash { width: 100%; padding: 18px; background: var(--success); color: white; border: none; border-radius: 12px; font-size: 18px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 12px; transition: all 0.3s; }
.btn-open-cash:hover { background: #16a34a; transform: translateY(-2px); box-shadow: 0 10px 30px rgba(34, 197, 94, 0.4); }
    .cash-modal-backdrop-static { pointer-events: auto; }
    .cash-modal-backdrop-static .modal-overlay { pointer-events: auto; }

    /* Gas Sale Type in Payment */
    .gas-sale-type { background: var(--bg-card); border-radius: 12px; padding: 16px; margin-bottom: 16px; border: 2px solid var(--accent); }
    .measure-selector { display: flex; gap: 8px; }
    .measure-btn { flex: 1; padding: 10px; border: 2px solid var(--border-color); border-radius: 8px; background: transparent; color: var(--text-secondary); font-weight: 600; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 13px; }
    .measure-btn.active { background: var(--accent); border-color: var(--accent); color: white; }
    .measure-btn:hover:not(.active) { border-color: var(--accent); color: var(--accent); }
    .gas-calc-result { background: var(--bg-main); border-radius: 10px; padding: 12px; margin-top: 12px; }
    .calc-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
    .calc-row:last-child { margin-bottom: 0; }
    .calc-row span:first-child { color: var(--text-secondary); }
    .calc-row span:last-child { font-weight: 600; color: var(--text-primary); }
    .calc-row:last-child span:last-child { color: var(--accent); font-size: 18px; }

    /* Customer Modal */
    .customer-modal { max-width: 480px; max-height: 90vh; overflow-y: auto; }
    .customer-form { padding: 24px; }

    /* Cash Detail Modal */
    .cash-detail-modal { max-width: 500px; overflow: hidden; }
    .cash-detail-header { text-align: center; padding: 32px 24px 24px; background: linear-gradient(135deg, var(--primary), #2a9aa8); color: white; }
    .cash-detail-header i { font-size: 48px; margin-bottom: 16px; display: block; }
    .cash-detail-header h2 { font-family: 'Montserrat', sans-serif; font-size: 24px; font-weight: 700; margin: 0; }
    .cash-detail-body { padding: 24px; }
    .cash-detail-loading { text-align: center; padding: 40px; font-size: 18px; color: var(--text-secondary); }
    .cash-detail-loading i { margin-right: 8px; }
    .cash-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .cash-detail-item { background: var(--bg-card); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); }
    .cash-detail-item.full { grid-column: 1 / -1; }
    .cash-detail-item .label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary); margin-bottom: 4px; }
    .cash-detail-item .value { font-size: 18px; font-weight: 700; color: var(--text-primary); }
    .cash-detail-item .value.success { color: var(--success); }
    .cash-detail-item .value.warning { color: var(--accent); }
    .cash-detail-item .value.danger { color: #ef4444; }
    .cash-detail-item .sub { font-size: 12px; color: var(--text-secondary); margin-top: 2px; }

  </style>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('vendeya-theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    document.getElementById('themeToggle').addEventListener('click', function() {
        const current = document.documentElement.getAttribute('data-theme');
        const newTheme = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('vendeya-theme', newTheme);
        document.querySelector('#themeToggle i').className = newTheme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
    });

    let cart = [];
    let activeFuelProduct = null;
    
    document.getElementById('gasPanelClose').addEventListener('click', function() {
        if (activeFuelProduct) {
            removeFuelFromCart(activeFuelProduct.id);
        }
    });
    
    // Initialize default customer
    document.getElementById('customerSelect').value = '1';
    updateCustomerPreview('Clientes - Varios', '00000000');
    
    // Gas panel event listeners
    let activeGasMode = 'amount';
    document.getElementById('gasByAmount').addEventListener('click', function() {
        document.getElementById('gasByAmount').classList.add('active');
        document.getElementById('gasByGallons').classList.remove('active');
        activeGasMode = 'amount';
        recalcGasPanel();
    });
    document.getElementById('gasByGallons').addEventListener('click', function() {
        document.getElementById('gasByGallons').classList.add('active');
        document.getElementById('gasByAmount').classList.remove('active');
        activeGasMode = 'gallons';
        recalcGasPanel();
    });
    function recalcGasPanel() {
        let val = parseFloat(document.getElementById('gasInputAmount').value) || 0;
        if (val < 0) { val = 0; document.getElementById('gasInputAmount').value = 0; }
        if (!activeFuelProduct) return;
        
        const gasPricePerGallon = activeFuelProduct.price;
        const isByAmount = document.getElementById('gasByAmount').classList.contains('active');
        const gallons = isByAmount ? (val / gasPricePerGallon) : val;
        const total = isByAmount ? val : (val * gasPricePerGallon);
        document.getElementById('gasGallonsDisplay').textContent = gallons.toFixed(2);
        document.getElementById('gasTotalDisplay').textContent = 'S/ ' + total.toFixed(2);
        // Update cart
        let fuelItem = cart.find(i => i.id === activeFuelProduct.id && i.isFuel);
        if (!fuelItem) {
            if (total <= 0) { return; }
            fuelItem = {
                id: activeFuelProduct.id,
                name: activeFuelProduct.name,
                price: activeFuelProduct.price,
                quantity: 1,
                isFuel: true,
                gas_amount: 0,
                gas_gallons: 0,
                gas_total: 0,
                gas_mode: 'amount'
            };
            cart.push(fuelItem);
        }
        fuelItem.gas_amount = val;
        fuelItem.gas_gallons = gallons;
        fuelItem.gas_total = total;
        fuelItem.gas_mode = isByAmount ? 'amount' : 'gallons';
        
        updateCart();
    }
    document.getElementById('gasInputAmount').addEventListener('input', recalcGasPanel);
    document.getElementById('plateInput').addEventListener('input', function() {
        updateReceiptPreview();
    });
    
    document.querySelectorAll('.product-card').forEach(card => {
        card.addEventListener('click', function() {
            const id = parseInt(this.dataset.id), name = this.dataset.name, price = parseFloat(this.dataset.price), category = this.dataset.category;
            const isFuel = this.dataset.isFuel === 'true';
            const fuelPrice = parseFloat(this.dataset.fuelPrice);
            
            if (isFuel) {
                showGasPanel(id, name, fuelPrice || price);
                return;
            }
            
            const existingItem = cart.find(item => item.id === id);
            if (existingItem) { existingItem.quantity++; } else { cart.push({ id, name, price, quantity: 1 }); }
            updateCart();
            showToast(`${name} agregado al carrito`);
        });
    });
    
    function showGasPanel(productId, productName, pricePerUnit) {
        if (activeFuelProduct && activeFuelProduct.id !== productId) {
            // Remove previous fuel item from cart when switching to a different fuel
            cart = cart.filter(item => !(item.isFuel));
        }
        
        const panel = document.getElementById('gasPanelCart');
        panel.style.display = 'block';
        
        document.getElementById('gasProductName').textContent = productName;
        document.getElementById('gasPriceDisplay').textContent = 'S/ ' + pricePerUnit.toFixed(2);
        document.getElementById('activeFuelId').value = productId;
        
        activeFuelProduct = { id: productId, name: productName, price: pricePerUnit };
        
        const existingFuel = cart.find(item => item.id === productId && item.isFuel);
        
        if (existingFuel && existingFuel.gas_total > 0) {
            document.getElementById('gasInputAmount').value = existingFuel.gas_amount || 0;
            document.getElementById('gasGallonsDisplay').textContent = (existingFuel.gas_gallons || 0).toFixed(2);
            document.getElementById('gasTotalDisplay').textContent = 'S/ ' + (existingFuel.gas_total || 0).toFixed(2);
            
            if (existingFuel.gas_mode === 'gallons') {
                document.getElementById('gasByAmount').classList.remove('active');
                document.getElementById('gasByGallons').classList.add('active');
                activeGasMode = 'gallons';
            } else {
                document.getElementById('gasByAmount').classList.add('active');
                document.getElementById('gasByGallons').classList.remove('active');
                activeGasMode = 'amount';
            }
        } else {
            document.getElementById('gasInputAmount').value = 0;
            document.getElementById('gasGallonsDisplay').textContent = '0.00';
            document.getElementById('gasTotalDisplay').textContent = 'S/ 0.00';
            document.getElementById('gasByAmount').classList.add('active');
            document.getElementById('gasByGallons').classList.remove('active');
            activeGasMode = 'amount';
        }
    }
    window.showGasPanel = showGasPanel;
    
    function removeFuelFromCart(id) {
        cart = cart.filter(item => !(item.id === id && item.isFuel));
        document.getElementById('gasInputAmount').value = 0;
        document.getElementById('gasGallonsDisplay').textContent = '0.00';
        document.getElementById('gasTotalDisplay').textContent = 'S/ 0.00';
        document.getElementById('gasPanelCart').style.display = 'none';
        activeFuelProduct = null;
        document.getElementById('activeFuelId').value = '';
        updateCart();
    }
    window.removeFuelFromCart = removeFuelFromCart;
    
    function updateCart() {
        const cartEmpty = document.getElementById('cartEmpty'), cartItems = document.getElementById('cartItems'), cartSummary = document.getElementById('cartSummary');
        if (cart.length === 0) {
            cartEmpty.style.display = 'flex';
            cartItems.innerHTML = '';
            cartSummary.style.display = 'none';
            document.getElementById('gasPanelCart').style.display = 'none';
            return;
        }
        cartEmpty.style.display = 'none'; cartSummary.style.display = 'block';
        
        cartItems.innerHTML = cart.map(item => {
            if (item.isFuel) {
                return `<div class="cart-item" data-id="${item.id}"><div class="cart-item-info"><div class="cart-item-name">${item.name}</div><div class="cart-item-price">${(item.gas_gallons || 0).toFixed(2)} GLL x S/ ${item.price.toFixed(2)}</div></div><div class="cart-item-total">S/ ${(item.gas_total || 0).toFixed(2)}</div><i class="fas fa-trash cart-item-delete" onclick="removeFuelFromCart(${item.id})"></i></div>`;
            }
            return `<div class="cart-item" data-id="${item.id}"><div class="cart-item-info"><div class="cart-item-name">${item.name}</div><div class="cart-item-price">S/ ${item.price.toFixed(2)} c/u</div></div><div class="cart-item-controls"><button class="qty-btn" onclick="updateQty(${item.id}, -1)"><i class="fas fa-minus"></i></button><input type="number" class="qty-input" value="${item.quantity}" min="1" onchange="setQty(${item.id}, this.value)" onkeydown="if(event.key==='Enter')setQty(${item.id}, this.value)"><button class="qty-btn" onclick="updateQty(${item.id}, 1)"><i class="fas fa-plus"></i></button></div><div class="cart-item-total">S/ ${(item.price * item.quantity).toFixed(2)}</div><i class="fas fa-trash cart-item-delete" onclick="removeFromCart(${item.id})"></i></div>`;
        }).join('');
        
        let total = 0;
        let hasFuel = false;
        cart.forEach(item => {
            if (item.isFuel) {
                if (item.gas_total && item.gas_total > 0) {
                    total += item.gas_total;
                }
                hasFuel = true;
            } else {
                total += item.price * item.quantity;
            }
        });
        
        if (!hasFuel) {
            document.getElementById('gasPanelCart').style.display = 'none';
            activeFuelProduct = null;
            document.getElementById('activeFuelId').value = '';
        }
        
        const productCount = cart.filter(item => {
            if (item.isFuel) {
                return item.gas_total && item.gas_total > 0;
            }
            return true;
        }).length;
        document.getElementById('totalProducts').textContent = productCount.toFixed(2);
        document.getElementById('totalAmount').textContent = `S/ ${total.toFixed(2)}`;
        document.getElementById('amountToPay').textContent = `S/ ${total.toFixed(2)}`;
        updateReceiptPreview(total);
    }
    function clearCart() {
        cart = [];
        payments = [];
        activeFuelProduct = null;
        document.getElementById('gasInputAmount').value = '';
        document.getElementById('gasGallonsDisplay').textContent = '0.00';
        document.getElementById('gasTotalDisplay').textContent = 'S/ 0.00';
        document.getElementById('gasPanelCart').style.display = 'none';
        document.getElementById('activeFuelId').value = '';
        updateCart();
    }
    window.updateQty = function(id, change) { const item = cart.find(i => i.id === id); if (item) { item.quantity += change; if (item.quantity <= 0) { removeFromCart(id); } else { updateCart(); } } };
    window.setQty = function(id, qty) { let newQty = parseInt(qty); const item = cart.find(i => i.id === id); if (item) { if (newQty <= 0) { removeFromCart(id); } else { item.quantity = newQty; updateCart(); } } };
    window.removeFromCart = function(id) { cart = cart.filter(item => item.id !== id); updateCart(); };

    function showToast(message) { const toast = document.createElement('div'); toast.className = 'welcome-toast'; toast.innerHTML = `<i class="fas fa-check-circle"></i><span>${message}</span>`; toast.style.animation = 'slideIn 0.4s ease'; document.body.appendChild(toast); setTimeout(() => { toast.style.animation = 'slideOut 0.4s ease forwards'; setTimeout(() => toast.remove(), 400); }, 2000); }

    const modal = document.getElementById('paymentModal'), btnCheckout = document.getElementById('btnCheckout'), closeModal = document.getElementById('closeModal');
    btnCheckout.addEventListener('click', () => {
        if (!cashOpened) { Swal.fire({ icon: 'error', title: 'Caja cerrada', text: 'Debe abrir caja antes de realizar ventas' }); return; }
        const activeItems = cart.filter(i => !i.isFuel || (i.gas_total && i.gas_total > 0));
        if (activeItems.length === 0) { Swal.fire({ icon: 'warning', title: 'Carrito vacío', text: 'Agrega productos antes de finalizar' }); return; }
        // Remove fuel items with zero amount
        cart = cart.filter(i => !i.isFuel || (i.gas_total && i.gas_total > 0));
        modal.classList.add('active'); 
        
        const cartTotal = getCartTotal();
        const amountToPayEl = document.getElementById('amountToPay');
        if (amountToPayEl) amountToPayEl.textContent = `S/ ${cartTotal.toFixed(2)}`;
        
        initPayments();
        updateSerieOptions('nv');
        updateReceiptPreview(cartTotal);
    });
    
    closeModal.addEventListener('click', () => { modal.classList.remove('active'); resetPaymentForm(); });
    modal.addEventListener('click', (e) => { if (e.target === modal) { modal.classList.remove('active'); resetPaymentForm(); } });

    document.querySelectorAll('.doc-tab').forEach(tab => { tab.addEventListener('click', function() { document.querySelectorAll('.doc-tab').forEach(t => t.classList.remove('active')); this.classList.add('active'); updateReceiptPreview(); const type = this.dataset.type; updateSerieOptions(type); if (type === 'vale') { loadValeProducts(); } }); });
    
    function loadValeProducts() {
        // Los productos ya están renderizados en la grilla, solo mostramos todos
        document.querySelectorAll('.product-card').forEach(c => c.style.display = 'block');
    }
    
    const customerSearchInput = document.getElementById('customerSearch');
    const customerSelectHidden = document.getElementById('customerSelect');
    const customerDropdown = document.getElementById('customerDropdown');
    const customerOptions = document.getElementById('customerOptions');
    const customerToggle = document.getElementById('customerToggle');
    let customerHighlightedIndex = 0;
    
    function getCustomerDataFromElement(el) {
        return {
            id: el.dataset.id,
            name: el.dataset.name,
            doc: el.dataset.doc
        };
    }
    
    function updateCustomerPreview(name, doc) {
        document.getElementById('previewCliente').textContent = name;
        document.getElementById('previewDoc').textContent = doc;
        
        const docType = document.querySelector('.doc-tab.active').dataset.type;
        const docOnlyNumbers = doc.replace(/\D/g, '');
        const docLen = docOnlyNumbers.length;
        const isAllZeros = /^0+$/.test(docOnlyNumbers) || docOnlyNumbers === '';
        const isRuc = docLen === 11 && !isAllZeros;
        const isDni = docLen === 8 && !isAllZeros;
        
        let docLabel = 'Doc:';
        if (docType === 'factura') {
            docLabel = 'RUC:';
        } else {
            if (isRuc) {
                docLabel = 'RUC:';
            } else if (isDni) {
                docLabel = 'DNI:';
            } else {
                docLabel = 'DNI/RUC:';
            }
        }
        document.getElementById('previewDocLabel').textContent = docLabel;
    }
    
    function filterCustomerOptions(searchTerm) {
        const term = searchTerm.toLowerCase().trim();
        const options = customerOptions.querySelectorAll('.combobox-option');
        
        if (term === '') {
            options.forEach(function(opt) { opt.style.display = ''; });
            return;
        }
        
        options.forEach(function(option) {
            const name = option.dataset.name.toLowerCase();
            const doc = option.dataset.doc;
            if (name.includes(term) || doc.includes(term)) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });
    }
    
    function showCustomerDropdown() {
        customerDropdown.classList.add('show');
    }
    
    function hideCustomerDropdown() {
        customerDropdown.classList.remove('show');
    }
    
    function selectCustomerOption(optionEl) {
        const data = getCustomerDataFromElement(optionEl);
        customerSearchInput.value = data.name + ' - ' + data.doc;
        customerSelectHidden.value = data.id;
        updateCustomerPreview(data.name, data.doc);
        hideCustomerDropdown();
    }
    
    if (customerSearchInput && customerDropdown) {
        customerSearchInput.addEventListener('focus', function() {
            showCustomerDropdown();
            filterCustomerOptions(this.value);
        });
        
        customerSearchInput.addEventListener('input', function() {
            filterCustomerOptions(this.value);
            customerHighlightedIndex = 0;
        });
        
        customerSearchInput.addEventListener('keydown', function(e) {
            const options = customerOptions.querySelectorAll('.combobox-option:not([style*="display: none"])');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!customerDropdown.classList.contains('show')) {
                    showCustomerDropdown();
                }
                customerHighlightedIndex = Math.min(customerHighlightedIndex + 1, options.length - 1);
                options.forEach(function(opt, idx) {
                    opt.classList.toggle('highlighted', idx === customerHighlightedIndex);
                });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                customerHighlightedIndex = Math.max(customerHighlightedIndex - 1, 0);
                options.forEach(function(opt, idx) {
                    opt.classList.toggle('highlighted', idx === customerHighlightedIndex);
                });
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const visibleOptions = customerOptions.querySelectorAll('.combobox-option:not([style*="display: none"])');
                if (visibleOptions[customerHighlightedIndex]) {
                    selectCustomerOption(visibleOptions[customerHighlightedIndex]);
                }
            } else if (e.key === 'Escape') {
                hideCustomerDropdown();
            } else if (e.key === 'Tab') {
                const term = this.value.toLowerCase().trim();
                const options = customerOptions.querySelectorAll('.combobox-option');
                let found = false;
                for (let i = 0; i < options.length; i++) {
                    const option = options[i];
                    if (option.dataset.name.toLowerCase() === term || option.dataset.doc === term) {
                        selectCustomerOption(option);
                        found = true;
                        break;
                    }
                }
                if (!found && term !== '') {
                    customerSelectHidden.value = '1';
                    updateCustomerPreview('Clientes - Varios', '00000000');
                }
                hideCustomerDropdown();
            }
        });
        
        customerToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            if (customerDropdown.classList.contains('show')) {
                hideCustomerDropdown();
            } else {
                customerSearchInput.focus();
                showCustomerDropdown();
                filterCustomerOptions(customerSearchInput.value);
            }
        });
        
        customerOptions.addEventListener('click', function(e) {
            const option = e.target.closest('.combobox-option');
            if (option) {
                selectCustomerOption(option);
            }
        });
        
        document.addEventListener('click', function(e) {
            if (!customerSearchInput.contains(e.target) && !customerToggle.contains(e.target) && !customerDropdown.contains(e.target)) {
                hideCustomerDropdown();
            }
        });
    }
    
    document.getElementById('customerSelect').addEventListener('change', function() {
        const customerText = this.options[this.selectedIndex].text;
        const parts = customerText.split(' - ');
        const customerName = parts[0].trim();
        const customerDoc = parts[parts.length - 1].trim();
        document.getElementById('previewCliente').textContent = customerName;
        document.getElementById('previewDoc').textContent = customerDoc;
        
        const docType = document.querySelector('.doc-tab.active').dataset.type;
        const docOnlyNumbers = customerDoc.replace(/\D/g, '');
        const docLen = docOnlyNumbers.length;
        const isAllZeros = /^0+$/.test(docOnlyNumbers) || docOnlyNumbers === '';
        const isRuc = docLen === 11 && !isAllZeros;
        const isDni = docLen === 8 && !isAllZeros;
        
        let docLabel = 'Doc:';
        if (docType === 'factura') {
            docLabel = 'RUC:';
        } else {
            if (isRuc) {
                docLabel = 'RUC:';
            } else if (isDni) {
                docLabel = 'DNI:';
            } else {
                docLabel = 'DNI/RUC:';
            }
        }
        document.getElementById('previewDocLabel').textContent = docLabel;
        
        // Consultar saldo del vale dinámicamente
        const voucherBalanceRow = document.getElementById('previewVoucherBalanceRow');
        const previewVoucherBalance = document.getElementById('previewVoucherBalance');
        const voucherBalanceInput = document.getElementById('voucherBalance');
        
        if (customerDoc && !isAllZeros) {
            fetch('{{ route("vendeya.api.vouchers.balance", ["doc" => "__DOC__"]) }}'.replace('__DOC__', customerDoc))
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.balance > 0) {
                        if (voucherBalanceRow && previewVoucherBalance) {
                            voucherBalanceRow.style.display = 'flex';
                            previewVoucherBalance.textContent = parseFloat(data.balance).toFixed(2);
                        }
                        if (voucherBalanceInput) {
                            voucherBalanceInput.value = 'S/ ' + parseFloat(data.balance).toFixed(2);
                        }
                    } else {
                        if (voucherBalanceRow) {
                            voucherBalanceRow.style.display = 'none';
                        }
                        if (voucherBalanceInput) {
                            voucherBalanceInput.value = 'S/ 0.00';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching voucher balance:', error);
                    if (voucherBalanceRow) voucherBalanceRow.style.display = 'none';
                    if (voucherBalanceInput) voucherBalanceInput.value = 'S/ 0.00';
                });
        } else {
            if (voucherBalanceRow) voucherBalanceRow.style.display = 'none';
            if (voucherBalanceInput) voucherBalanceInput.value = 'S/ 0.00';
        }
    });
    
    document.getElementById('serieSelect').addEventListener('change', function() {
        const serie = this.value;
        const revMap = { 'NV01': 'nv', 'B001': 'boleta', 'F001': 'factura', 'V001': 'vale' };
        const type = revMap[serie] || 'nv';
        document.querySelectorAll('.doc-tab').forEach(t => t.classList.remove('active'));
        document.querySelector(`.doc-tab[data-type="${type}"]`).classList.add('active');
        updateSerieOptions(type);
        updateReceiptPreview();
    });

    document.querySelectorAll('.quick-btn').forEach(btn => { btn.addEventListener('click', function() { 
        const cartTotal = getCartTotal();
        const amount = this.dataset.amount === 'exact' ? cartTotal : parseFloat(this.dataset.amount) || 0;
        if (payments.length === 0) {
            payments.push({ methodCode: '01', amount: amount });
        } else if (payments.length === 1) {
            payments[0].amount = amount;
        } else {
            // Distribute proportionally across payments
            const currentTotal = getTotalPaid();
            if (currentTotal > 0) {
                const ratio = amount / currentTotal;
                payments.forEach(p => { p.amount = (parseFloat(p.amount) || 0) * ratio; });
            } else {
                const each = amount / payments.length;
                payments.forEach(p => { p.amount = each; });
            }
        }
        renderPayments();
        updatePaymentsTotal();
    }); });

    document.getElementById('btnAddPayment').addEventListener('click', addPayment);

    function updateSerieOptions(type) {
        const docTypeMap = { nv: '80', boleta: '03', factura: '01', vale: '80' };
        const fallbackMap = {
            nv:   { id: 10, number: 'NV01', document_type_id: '80', establishment_id: 1, prefix: 'NV' },
            boleta: { id: 1, number: 'B001', document_type_id: '03', establishment_id: 1, prefix: 'B' },
            factura: { id: 2, number: 'F001', document_type_id: '01', establishment_id: 1, prefix: 'F' },
            vale:  { id: 3, number: 'V001', document_type_id: '80', establishment_id: 1, prefix: 'V' }
        };
        const docTypeId = docTypeMap[type] || '80';
        const select = document.getElementById('serieSelect');
        select.innerHTML = '';
        var matching = seriesData.filter(function(s) { return s.document_type_id === docTypeId; });
        if (matching.length === 0) {
            var fb = fallbackMap[type] || fallbackMap.nv;
            matching = [fb];
        }
        matching.forEach(function(s) {
            var opt = document.createElement('option');
            opt.value = s.number;
            opt.textContent = s.number;
            opt.dataset.seriesId = s.id;
            opt.dataset.establishmentId = s.establishment_id;
            opt.dataset.prefix = s.prefix || s.number.replace(/[0-9]/g, '');
            select.appendChild(opt);
        });
    }

    function updateReceiptPreview(externTotal = null) { 
        const docType = document.querySelector('.doc-tab.active').dataset.type;
        const titles = { nv: 'NOTA DE VENTA', boleta: 'BOLETA DE VENTA ELECTRÓNICA', factura: 'FACTURA ELECTRÓNICA', vale: 'VALE DE VENTA' };
        
        let customerName = 'Clientes - Varios';
        let customerDoc = '00000000';
        
        const customerSelect = document.getElementById('customerSelect');
        if (customerSelect) {
            const customerText = customerSelect.options[customerSelect.selectedIndex].text;
            const parts = customerText.split(' - ');
            customerName = parts[0].trim();
            customerDoc = parts.length > 1 ? parts[parts.length - 1].trim() : '00000000';
        }
        
        const now = new Date();
        const fecha = now.toISOString().split('T')[0];
        const hora = now.toTimeString().split(' ')[0].split('.')[0];
        
        document.getElementById('previewTitle').textContent = titles[docType] || 'NOTA DE VENTA';
        document.getElementById('previewFecha').textContent = fecha;
        document.getElementById('previewHora').textContent = hora;
        document.getElementById('previewCliente').textContent = customerName;
        document.getElementById('previewDoc').textContent = customerDoc;
        
        // Determinar tipo de documento según longitud (DNI=8, RUC=11)
        const customerDocClean = customerDoc ? customerDoc.trim() : '';
        const docOnlyNumbers = customerDocClean.replace(/\D/g, '');
        const docLen = docOnlyNumbers.length;
        
        // Verificar si es un documento válido (no puede ser todo ceros)
        const isAllZeros = /^0+$/.test(docOnlyNumbers) || docOnlyNumbers === '';
        const isRuc = docLen === 11 && !isAllZeros;
        const isDni = docLen === 8 && !isAllZeros;
        
        // Label según tipo de documento
        let docLabel = 'Doc:';
        if (docType === 'factura') {
            docLabel = 'RUC:';
        } else {
            if (isRuc) {
                docLabel = 'RUC:';
            } else if (isDni) {
                docLabel = 'DNI:';
            } else {
                docLabel = 'DNI/RUC:';
            }
        }
        document.getElementById('previewDocLabel').textContent = docLabel;
        
        let cartTotal = 0;
        let hasItems = false;
        
        if (externTotal !== null) {
            cartTotal = externTotal;
            hasItems = cart.some(item => {
                if (item.isFuel) return item.gas_total > 0;
                return true;
            });
        } else {
            cart.forEach(item => {
                if (item.isFuel) {
                    if (item.gas_total && item.gas_total > 0) {
                        cartTotal += item.gas_total;
                        hasItems = true;
                    }
                } else {
                    cartTotal += item.price * item.quantity;
                    hasItems = true;
                }
            });
        }
        let itemsHtml = '';
        
        if (!hasItems || cartTotal === 0) {
            itemsHtml = '<tr class="empty-row"><td colspan="5">Productos aparecerán aquí</td></tr>';
        } else {
            cart.forEach(item => {
                const isGas = item.isFuel;
                if (isGas && (!item.gas_total || item.gas_total <= 0)) {
                    return;
                }
                const qty = isGas && item.gas_gallons ? item.gas_gallons.toFixed(2) : item.quantity;
                const unit = isGas ? 'GLL' : 'NIU';
                const itemTotal = isGas && item.gas_total ? item.gas_total : (item.price * item.quantity);
                const displayPrice = isGas ? (item.gas_total && item.gas_gallons ? (item.gas_total / item.gas_gallons).toFixed(2) : item.price.toFixed(2)) : item.price.toFixed(2);
                itemsHtml += `<tr>
                    <td>${qty}</td>
                    <td>${unit}</td>
                    <td>${item.name}</td>
                    <td>S/ ${displayPrice}</td>
                    <td>S/ ${itemTotal.toFixed(2)}</td>
                </tr>`;
            });
        }
        
        const isNotaVenta = docType === 'nv';
        const subtotal = isNotaVenta ? cartTotal : cartTotal / 1.18;
        const igv = isNotaVenta ? 0 : cartTotal - subtotal;
        
        document.getElementById('previewItems').innerHTML = itemsHtml;
        
        const subtotalEl = document.getElementById('previewSubtotal');
        const igvEl = document.getElementById('previewIgv');
        
        if (subtotalEl && igvEl) {
            if (isNotaVenta) {
                subtotalEl.parentElement.style.display = 'none';
                igvEl.parentElement.style.display = 'none';
            } else {
                subtotalEl.parentElement.style.display = 'flex';
                igvEl.parentElement.style.display = 'flex';
                subtotalEl.textContent = 'S/ ' + subtotal.toFixed(2);
                igvEl.textContent = 'S/ ' + igv.toFixed(2);
            }
        }
        
        document.getElementById('previewTotal').textContent = 'S/ ' + cartTotal.toFixed(2);
        document.getElementById('previewSon').textContent = numeroALetras(cartTotal) + ' con ' + (cartTotal % 1).toFixed(2).substring(2) + '/100 Soles';
        
        // Update payment methods and saldo in preview
        const totalPaid = getTotalPaid();
        const previewPaidEl = document.getElementById('previewPaid');
        if (previewPaidEl) {
            const previewPaidParent = previewPaidEl.parentElement;
            if (previewPaidParent) {
                if (payments.length > 0 && payments.some(p => parseFloat(p.amount) > 0)) {
                    previewPaidParent.innerHTML = payments.map((p, i) => 
                        `${i + 1}-${getMethodName(p.methodCode)} S/ ${(parseFloat(p.amount) || 0).toFixed(2)}`
                    ).join(' + ') + ` <span id="previewPaid" style="display:none"></span>`;
                } else {
                    previewPaidParent.innerHTML = `Efectivo - S/ <span id="previewPaid">${cartTotal.toFixed(2)}</span>`;
                }
            }
        }
        const previewSaldo = document.getElementById('previewSaldo');
        if (previewSaldo) {
            previewSaldo.textContent = `S/ ${Math.max(0, totalPaid - cartTotal).toFixed(2)}`;
        }
        
        // Plate number - only show for factura
        const plateRow = document.getElementById('previewPlateRow');
        const plateInput = document.getElementById('plateInput');
        const plateValue = plateInput ? plateInput.value.toUpperCase() : '';
        const activeTab = document.querySelector('.doc-tab.active');
        const currentDocType = activeTab ? activeTab.dataset.type : 'nv';
        
        if (plateValue) {
            plateRow.style.display = 'flex';
            document.getElementById('previewPlate').textContent = plateValue;
        } else {
            plateRow.style.display = 'none';
        }
    }
    
    function numeroALetras(numero) {
        const unidades = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
        const decenas = ['', 'diez', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
        const especiales = ['diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve'];
        
        const partes = numero.toFixed(2).split('.');
        const entero = parseInt(partes[0]);
        const decimal = parseInt(partes[1]);
        
        let resultado = '';
        if (entero === 0) {
            resultado = 'cero';
        } else if (entero < 10) {
            resultado = unidades[entero];
        } else if (entero < 20) {
            resultado = especiales[entero - 10];
        } else if (entero < 100) {
            const dec = Math.floor(entero / 10);
            const uni = entero % 10;
            resultado = decenas[dec] + (uni > 0 ? ' y ' + unidades[uni] : '');
        } else if (entero < 1000) {
            const cent = Math.floor(entero / 100);
            const resto = entero % 100;
            resultado = (cent === 1 ? 'ciento' : unidades[cent] + 'cientos') + (resto > 0 ? ' ' + (resto < 10 ? unidades[resto] : (resto < 20 ? especiales[resto - 10] : decenas[Math.floor(resto / 10)] + (resto % 10 > 0 ? ' y ' + unidades[resto % 10] : ''))) : '');
        } else {
            resultado = entero.toString();
        }
        
        return resultado.charAt(0).toUpperCase() + resultado.slice(1);
    }

    document.getElementById('btnFinalize').addEventListener('click', async function() {
        if (!cashOpened) {
            Swal.fire({ icon: 'error', title: 'Caja cerrada', text: 'La caja se cerró. Debe abrir caja antes de finalizar la venta' });
            return;
        }
        let cartTotal = getCartTotal();
        
        if (cartTotal <= 0) {
            Swal.fire({ icon: 'error', title: 'Carrito vacío', text: 'Agrega productos al carrito antes de finalizar' });
            return;
        }
        
        const customerSelect = document.getElementById('customerSelect');
        const customerSearch = document.getElementById('customerSearch');
        if (!customerSearch.value.trim()) {
            customerSearch.value = 'Clientes - Varios - 00000000';
            customerSelect.value = '1';
            updateCustomerPreview('Clientes - Varios', '00000000');
        }
        
        const docType = document.querySelector('.doc-tab.active').dataset.type;
        const titles = { nv: 'NOTA DE VENTA', boleta: 'BOLETA ELECTRÓNICA', factura: 'FACTURA ELECTRÓNICA', vale: 'VALE DE VENTA' };
        const series = { nv: 'NV01', boleta: 'B001', factura: 'F001', vale: 'V001' };
        const customerId = customerSelect.value;
        const customerName = customerSelect.options[customerSelect.selectedIndex].text;
        
        const paid = getTotalPaid();
        
        if (docType !== 'vale' && paid < cartTotal) { Swal.fire({ icon: 'error', title: 'Monto insuficiente', text: `Falta S/ ${(cartTotal - paid).toFixed(2)}` }); this.disabled = false; this.innerHTML = '<i class="fas fa-check"></i> Finalizar Venta'; return; }
        
        if (docType === 'vale' || hasValePayment()) {
            if (paid <= 0) {
                Swal.fire({ icon: 'error', title: 'Monto inválido', text: 'Ingresa un monto para el vale' });
                this.disabled = false; this.innerHTML = '<i class="fas fa-check"></i> Finalizar Venta';
                return;
            }
            const voucherDoc = document.getElementById('voucherCustomerDoc').value.trim();
            if (!voucherDoc) {
                Swal.fire({ icon: 'error', title: 'Campo requerido', text: 'Ingresa el DNI/RUC del cliente para el vale' });
                this.disabled = false; this.innerHTML = '<i class="fas fa-check"></i> Finalizar Venta';
                return;
            }
        }
        
        this.disabled = true; this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        
        const gasItem = cart.find(i => i.isFuel);
        const gasTotal = (gasItem && gasItem.gas_total > 0) ? gasItem.gas_total : 0;
        
        // Si es vale, crear vale via API
        if (docType === 'vale' || hasValePayment()) {
            const valeData = {
                document_type: 'vale',
                serie: document.getElementById('serieSelect').value || 'V001',
                customer_id: customerId,
                customer_doc: (customerName.split(' - ').pop() || '').trim(),
                plate_number: document.getElementById('plateInput').value.toUpperCase(),
                items: cart.map(item => ({
                    id: item.id,
                    name: item.name,
                    price: item.isFuel && item.gas_total ? item.gas_total / (item.gas_gallons || 1) : item.price,
                    quantity: item.isFuel && item.gas_gallons ? item.gas_gallons : item.quantity,
                    unit_type_id: item.isFuel ? 'GLL' : 'NIU'
                })),
                total: cartTotal,
                payment_method: '05',
                customer_name: customerName.split(' - ')[0] || customerName
            };
            
            try {
                const response = await fetch('{{ route("vendeya.sale.create") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(valeData)
                });
                
                if (!response.ok) {
                    const text = await response.text();
                    let errorMsg = 'HTTP ' + response.status;
                    try { const errJson = JSON.parse(text); errorMsg = errJson.message || errJson.error || errorMsg; } catch(e) {}
                    Swal.fire({ icon: 'error', title: 'Error', text: errorMsg });
                    this.disabled = false; this.innerHTML = '<i class="fas fa-check"></i> Finalizar Venta';
                    return;
                }
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('paymentForm').style.display = 'none';
                    document.getElementById('paymentSuccess').style.display = 'flex';
                    document.getElementById('receiptPreview').style.display = 'none';
                    
                    const now = new Date();
                    const fecha = now.toLocaleDateString('es-PE');
                    const hora = now.toLocaleTimeString('es-PE');
                    const docNumber = result.data?.series + '-' + result.data?.number || 'V001-' + Math.floor(Math.random() * 9999);
                    const vendorName = '{{ $user["name"] ?? "Demo" }}';
                    
                    refreshVoucherBalance();
                    
                    generateThermalTicket('vale', 'VALE DE VENTA', docNumber, fecha, hora, customerName, cart, cartTotal, paid, (paid - cartTotal).toFixed(2), vendorName, payments, null);
                    clearCart();
                    printThermalTicket();
                    Swal.fire({ icon: 'success', title: 'Vale creado', text: 'Vale registrado correctamente' });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: result.message || 'Error al crear vale' });
                }
            } catch (error) {
                console.error('Vale fetch error:', error);
                Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'Error de conexión al crear vale' });
            }
            
            this.disabled = false; this.innerHTML = '<i class="fas fa-check"></i> Finalizar Venta';
            return;
        }
        
        const serieEl = document.getElementById('serieSelect');
        const selectedSerie = serieEl.options[serieEl.selectedIndex];
        const saleData = {
            document_type: docType,
            serie: serieEl.value,
            series_id: selectedSerie ? parseInt(selectedSerie.dataset.seriesId) : null,
            establishment_id: selectedSerie ? parseInt(selectedSerie.dataset.establishmentId) : null,
            prefix: selectedSerie ? selectedSerie.dataset.prefix : null,
            customer_id: customerId,
            plate_number: document.getElementById('plateInput').value.toUpperCase(),
            has_gas_sale: gasTotal > 0,
            gas_info: gasTotal > 0 ? { gallons: gasItem.gas_gallons, total: gasTotal, fuel_product_id: gasItem.id, fuel_name: gasItem.name } : null,
            items: cart.map(item => {
                if (item.isFuel) {
                    return {
                        id: item.id,
                        name: item.name,
                        price: item.price,
                        quantity: item.gas_gallons || 0,
                        unit_type_id: 'GLL'
                    };
                }
                return {
                    id: item.id,
                    name: item.name,
                    price: item.price,
                    quantity: item.quantity,
                    unit_type_id: 'NIU'
                };
            }),
            total: cartTotal,
            paid: paid,
            pagos: payments.map(p => ({
                codigo_metodo_pago: p.methodCode,
                monto: parseFloat(p.amount) || 0
            }))
        };
        
        try {
            const response = await fetch('{{ route("vendeya.sale.create") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(saleData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                document.getElementById('paymentForm').style.display = 'none'; 
                document.getElementById('paymentSuccess').style.display = 'flex'; 
                document.getElementById('receiptPreview').style.display = 'none';
                
                currentExternalId = result.data.external_id;
                
                const now = new Date(); 
                const fecha = now.toLocaleDateString('es-PE'); 
                const hora = now.toLocaleTimeString('es-PE');
                const docNumber = result.data.document_id || (series[docType] + '-' + String(Math.floor(Math.random() * 99999999)).padStart(8, '0'));
                const change = (paid - cartTotal).toFixed(2);
                const vendorName = '{{ $user["name"] ?? "Demo" }}';
                
                generateThermalTicket(docType, titles[docType], docNumber, fecha, hora, customerName, cart, cartTotal, paid, change, vendorName, payments, currentVoucherBalance);
                
                clearCart();
                
                printThermalTicket();
                
                if (result.data.document_data) {
                    currentDocumentData = result.data.document_data;
                }
            } else {
                console.error('Sale error response:', result);
                Swal.fire({ icon: 'error', title: 'Error', text: result.message || result.msg || 'No se pudo procesar la venta' });
            }
        } catch (error) {
            console.error('Sale error:', error);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión con el servidor' });
        }
        
        this.disabled = false; this.innerHTML = '<i class="fas fa-check"></i> Finalizar Venta';
    });

let currentExternalId = null;
    let currentDocumentData = null;
    let currentVoucherBalance = 0;
    let payments = [];
    const methodNames = { '01': 'Efectivo', '02': 'Tarjeta Crédito', '03': 'Tarjeta Débito', '04': 'Transferencia', '05': 'Vale de Venta' };

    function getMethodName(code) { return methodNames[code] || 'Efectivo'; }

    function getTotalPaid() {
        return payments.reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0);
    }

    function getCartTotal() {
        let t = 0;
        cart.forEach(item => { t += item.isFuel && item.gas_total ? item.gas_total : (item.price * item.quantity); });
        return t;
    }

    function hasValePayment() {
        return payments.some(p => p.methodCode === '05');
    }

    function renderPayments() {
        const container = document.getElementById('paymentsContainer');
        if (!container) return;
        const cartTotal = getCartTotal();
        let html = '';
        payments.forEach((p, i) => {
            html += `
                <div class="payment-row" data-index="${i}">
                    <span class="payment-num">${i + 1}</span>
                    <select class="payment-method-select" data-index="${i}">
                        <option value="01" ${p.methodCode === '01' ? 'selected' : ''}>Efectivo</option>
                        <option value="02" ${p.methodCode === '02' ? 'selected' : ''}>Tarjeta Crédito</option>
                        <option value="03" ${p.methodCode === '03' ? 'selected' : ''}>Tarjeta Débito</option>
                        <option value="04" ${p.methodCode === '04' ? 'selected' : ''}>Transferencia</option>
                        <option value="05" ${p.methodCode === '05' ? 'selected' : ''}>Vale de Venta</option>
                    </select>
                    <div class="payment-amount-wrap">
                        <span class="money-prefix-sm">S/</span>
                        <input type="number" class="payment-amount-input" data-index="${i}" value="${p.amount.toFixed(2)}" min="0" step="0.01">
                    </div>
                    ${payments.length > 1 ? `<button class="payment-del-btn" data-index="${i}" title="Eliminar pago"><i class="fas fa-times"></i></button>` : '<div style="width:32px;flex-shrink:0"></div>'}
                </div>`;
        });
        container.innerHTML = html;

        // Bind change events for method selects
        container.querySelectorAll('.payment-method-select').forEach(sel => {
            sel.addEventListener('change', function() {
                const idx = parseInt(this.dataset.index);
                payments[idx].methodCode = this.value;
                document.getElementById('voucherSection').style.display = hasValePayment() ? 'block' : 'none';
                if (!hasValePayment()) {
                    currentVoucherBalance = 0;
                    const vbr = document.getElementById('previewVoucherBalanceRow');
                    if (vbr) vbr.style.display = 'none';
                }
                renderPayments();
            });
        });

        // Bind input events for amount inputs
        container.querySelectorAll('.payment-amount-input').forEach(inp => {
            inp.addEventListener('input', function() {
                const idx = parseInt(this.dataset.index);
                payments[idx].amount = Math.max(0, parseFloat(this.value) || 0);
                updatePaymentsTotal();
            });
        });

        // Bind click events for delete buttons
        container.querySelectorAll('.payment-del-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const idx = parseInt(this.dataset.index);
                removePayment(idx);
            });
        });

        updatePaymentsTotal();
    }

    function updatePaymentsTotal() {
        const totalPaid = getTotalPaid();
        const cartTotal = getCartTotal();
        const change = Math.max(0, totalPaid - cartTotal);
        const totalPaidEl = document.getElementById('totalPaidDisplay');
        const changeEl = document.getElementById('changeAmount');
        const saldoEl = document.getElementById('previewSaldo');
        if (totalPaidEl) totalPaidEl.textContent = `S/ ${totalPaid.toFixed(2)}`;
        if (changeEl) changeEl.textContent = `S/ ${change.toFixed(2)}`;
        if (saldoEl) saldoEl.textContent = `S/ ${change.toFixed(2)}`;
        updateReceiptPreview(cartTotal);
    }

    function addPayment() {
        payments.push({ methodCode: '01', amount: 0 });
        renderPayments();
    }

    function removePayment(index) {
        if (payments.length <= 1) return;
        payments.splice(index, 1);
        if (!hasValePayment()) {
            document.getElementById('voucherSection').style.display = 'none';
        }
        renderPayments();
    }

    function initPayments() {
        const cartTotal = getCartTotal();
        payments = [{ methodCode: '01', amount: cartTotal }];
        renderPayments();
        document.getElementById('voucherSection').style.display = 'none';
        currentVoucherBalance = 0;
    }

    const apiDomain = '{{ $apiDomain }}';
    const logoUrl = '{{ $logo }}';
    const seriesData = @json($series);
    const establishmentsData = @json($establishments);
    
    function generateThermalTicket(docType, docTitle, docNumber, fecha, hora, customerName, cartItems, total, paid, change, vendor, paymentsList, voucherBalance = 0) {
        let paymentHtml = '';
        if (Array.isArray(paymentsList)) {
            paymentsList.forEach(p => {
                const name = getMethodName(p.methodCode);
                paymentHtml += `<div>${fecha.split('/').reverse().join('/')} - ${name} - S/ ${(parseFloat(p.amount) || 0).toFixed(2)}</div>`;
            });
        } else {
            paymentHtml = `<div>${fecha.split('/').reverse().join('/')} - Efectivo - S/ ${paid.toFixed(2)}</div>`;
        }
        if (!paymentHtml) {
            paymentHtml = `<div>${fecha.split('/').reverse().join('/')} - Efectivo - S/ ${paid.toFixed(2)}</div>`;
        }
        
        let ticket = `<div class="thermal-header">
            <img src="${logoUrl}" alt="FactReady Lite" style="max-width: 100px; height: auto; margin-bottom: 8px;">
            <div>RUC: 44444444444</div>
            <div>CALLE DE PRUEBA , AREQUIPA ,</div>
            <div>AREQUIPA - AREQUIPA</div>
            <div>factreadylite@gmail.com</div>
            <div>&nbsp;</div>
            <div>9999999999</div>
        </div>
        <div class="thermal-doc-title">${docTitle}</div>
        <div class="thermal-doc-num">${docNumber}</div>
        <div class="thermal-divider"></div>
        <div>F. Emisión: ${fecha} / ${hora}</div>
        <div>Cliente: ${customerName}</div>
        <div>Doc.trib.no.dom.sin.ruc: 99999999</div>
        <div>Dirección: , , -</div>
        <div>Vendedor: ${vendor}</div>
        <div class="thermal-divider"></div>
        <div class="thermal-table-header">CANT.  UNIDAD  DESCRIPCIÓN  P.UNIT  TOTAL</div>
        <div class="thermal-divider"></div>`;
        
        cartItems.forEach(item => {
            if (item.isFuel) {
                const qty = item.gas_gallons ? item.gas_gallons.toFixed(2) : '0.00';
                const itemTotal = item.gas_total || 0;
                const unitPrice = item.gas_total && item.gas_gallons ? (item.gas_total / item.gas_gallons).toFixed(2) : (item.price || 0).toFixed(2);
                ticket += `<div class="thermal-item">
                    <span>${qty} GLL  ${item.name}  S/ ${unitPrice}</span>
                    <span>S/ ${itemTotal.toFixed(2)}</span>
                </div>`;
            } else {
                const unitPrice = item.price.toFixed(2);
                ticket += `<div class="thermal-item">
                    <span>${item.quantity} NIU  ${item.name}  S/ ${unitPrice}</span>
                    <span>S/ ${(item.price * item.quantity).toFixed(2)}</span>
                </div>`;
            }
        });
        
        ticket += `<div class="thermal-divider"></div>
        <div class="thermal-total">TOTAL A PAGAR: S/ ${total.toFixed(2)}</div>
        <div class="thermal-divider"></div>
        <div class="thermal-payment">PAGOS:</div>
        ${paymentHtml}
        <div>&nbsp;</div>
        ${voucherBalance > 0 ? `<div style="background: #d4edda; padding: 4px; border-radius: 4px; margin: 4px 0;"><strong>SALDO VALE: S/ ${voucherBalance.toFixed(2)}</strong></div>` : ''}
        <div>SALDO: S/ ${change}</div>
        <div class="thermal-link">Para consultar el comprobante ingresar a<br>https://${apiDomain}/buscar</div>`;
        
        const thermalEl = document.getElementById('thermalTicket');
        if (thermalEl) thermalEl.innerHTML = ticket;
    }

    document.getElementById('btnNewSale').addEventListener('click', function() { 
        cart = [];
        activeFuelProduct = null;
        
        document.getElementById('gasInputAmount').value = '';
        document.getElementById('gasGallonsDisplay').textContent = '0.00';
        document.getElementById('gasTotalDisplay').textContent = 'S/ 0.00';
        document.getElementById('gasPanelCart').style.display = 'none';
        document.getElementById('activeFuelId').value = '';
        
        document.getElementById('customerSelect').value = '1';
        updateCustomerPreview('Clientes - Varios', '00000000');
        
        updateCart(); 
        modal.classList.remove('active'); 
        document.getElementById('paymentModal').style.display = 'none';
        resetPaymentForm(); 
        updateReceiptPreview();
        
        setTimeout(() => {
            document.getElementById('paymentModal').style.display = '';
            document.getElementById('productsGrid').style.display = '';
        }, 100);
    });
    document.getElementById('btnViewPdf').addEventListener('click', function() { 
        if (currentDocumentData && currentDocumentData.external_id) {
            currentExternalId = currentDocumentData.external_id;
        } else if (currentDocumentData && currentDocumentData.document_id) {
            currentExternalId = currentDocumentData.document_id;
        }
        
        if (!currentExternalId) {
            document.getElementById('paymentSuccess').style.display = 'none'; 
            document.getElementById('pdfPreview').style.display = 'flex'; 
            return;
        }
        
        const docType = currentDocumentData?.tipo_documento || '80';
        const pdfUrl = docType === '80' 
            ? `https://${apiDomain}/sale-notes/print/${currentExternalId}/ticket`
            : `https://${apiDomain}/print/document/${currentExternalId}/ticket`;
        console.log('Loading PDF from:', pdfUrl);
        
        const thermalPaper = document.getElementById('thermalPaper');
        if (thermalPaper) {
            thermalPaper.style.display = 'none';
        }
        
        const pdfViewer = document.getElementById('pdfViewer');
        if (pdfViewer) {
            pdfViewer.innerHTML = `<iframe id="pdfIframe" src="${pdfUrl}" style="width:100%;height:100%;border:none;" onload="this.style.display='block'"></iframe>`;
        }
        
        const btnDownloadPdf = document.getElementById('btnDownloadPdf');
        if (btnDownloadPdf) {
            btnDownloadPdf.onclick = function() {
                window.open(pdfUrl, '_blank');
            };
        }
        
        const btnPrintPdf = document.getElementById('btnPrintPdf');
        if (btnPrintPdf) {
            btnPrintPdf.onclick = function() {
                const iframe = document.getElementById('pdfIframe');
                if (iframe && iframe.contentWindow) {
                    iframe.contentWindow.print();
                }
            };
        }
        
        document.getElementById('paymentSuccess').style.display = 'none'; 
        document.getElementById('pdfPreview').style.display = 'flex';
    });
    
    function renderDocumentPreview(doc) {
        const company = doc.company || {
            number: '44444444444',
            address: 'CALLE DE PRUEBA , AREQUIPA ,',
            country: 'AREQUIPA - AREQUIPA',
            email: 'factreadylite@gmail.com',
            telephone: '9999999999'
        };
        
        const customer = doc.customer || {
            name: doc.customer_name || 'Clientes - Varios',
            number: doc.customer_number || '00000000',
            address: doc.customer_address || ''
        };
        
        const items = doc.items || [];
        const totals = doc.totals || {
            total: doc.total || 0,
            total_igv: doc.total_igv || 0,
            total_value: doc.total_value || 0
        };
        
        const fechaEmision = doc.date_of_issue || '';
        const horaEmision = doc.time_of_issue || '';
        const docNumber = (doc.series || 'NV01') + '-' + (doc.number || '1');
        const docTitle = doc.document_type === '01' ? 'FACTURA ELECTRÓNICA' : (doc.document_type === '03' ? 'BOLETA ELECTRÓNICA' : 'NOTA DE VENTA');
        
        let preview = `<div class="document-preview">
            <div class="doc-header">
                <img src="${logoUrl}" alt="FactReady Lite" style="max-width: 100px; height: auto; margin-bottom: 8px;">
                <div class="doc-company-ruc">RUC: ${company.number || '44444444444'}</div>
                <div class="doc-company-address">${company.address || 'CALLE DE PRUEBA , AREQUIPA ,'}</div>
                <div class="doc-company-country">${company.country || 'AREQUIPA - AREQUIPA'}</div>
                <div class="doc-company-email">${company.email || 'factreadylite@gmail.com'}</div>
                <div class="doc-company-phone">${company.telephone || '9999999999'}</div>
            </div>
            <div class="doc-title">${docTitle}</div>
            <div class="doc-number">${docNumber}</div>
            <div class="doc-divider"></div>
            <div class="doc-info">F. Emisión: ${fechaEmision} / ${horaEmision}</div>
            <div class="doc-customer">Cliente: ${customer.name || 'Clientes - Varios'}</div>
            <div class="doc-customer-doc">Doc. Identidad: ${customer.number || '00000000'}</div>
            <div class="doc-customer-address">Dirección: ${customer.address || '-'}</div>
            <div class="doc-divider"></div>
            <table class="doc-items">
                <thead><tr><th>CANT</th><th>UND</th><th>DESCRIPCIÓN</th><th>P.UNIT</th><th>TOTAL</th></tr></thead>
                <tbody>`;
        
        items.forEach(item => {
            preview += `<tr>
                <td>${item.quantity}</td>
                <td>${item.unit_type_id || 'NIU'}</td>
                <td>${item.description || item.item?.description || '-'}</td>
                <td>S/ ${parseFloat(item.unit_price || 0).toFixed(2)}</td>
                <td>S/ ${parseFloat(item.total || 0).toFixed(2)}</td>
            </tr>`;
        });
        
        preview += `</tbody></table>
            <div class="doc-divider"></div>
            <div class="doc-totals">
                <div>Subtotal: S/ ${parseFloat(totals.total_value || 0).toFixed(2)}</div>
                <div>IGV (18%): S/ ${parseFloat(totals.total_igv || 0).toFixed(2)}</div>
                <div class="doc-total"><strong>TOTAL A PAGAR: S/ ${parseFloat(totals.total || 0).toFixed(2)}</strong></div>
            </div>
            <div class="doc-divider"></div>
            <div class="doc-payment">
                <div>Condición de pago: CONTADO</div>
                <div>Efectivo: S/ ${parseFloat(doc.paid || 0).toFixed(2)}</div>
                <div>Vuelto: S/ ${parseFloat(doc.change || 0).toFixed(2)}</div>
            </div>
            <div class="doc-footer">Para consultar el comprobante ingresar a<br>https://${apiDomain}/buscar</div>
        </div>`;
        
        const thermalPreview = document.getElementById('thermalTicket');
        if (thermalPreview) thermalPreview.innerHTML = preview;
    }
    document.getElementById('btnBackForm').addEventListener('click', function() { document.getElementById('pdfPreview').style.display = 'none'; document.getElementById('paymentSuccess').style.display = 'flex'; });

    function resetPaymentForm() { 
        document.getElementById('paymentForm').style.display = 'block'; 
        document.getElementById('paymentSuccess').style.display = 'none'; 
        document.getElementById('pdfPreview').style.display = 'none'; 
        document.getElementById('receiptPreview').style.display = 'block'; 
        document.getElementById('plateInput').value = ''; 
        document.querySelectorAll('.doc-tab').forEach(t => t.classList.remove('active')); 
        document.querySelector('.doc-tab').classList.add('active'); 
        updateSerieOptions('nv');
        
        // Ocultar saldo del vale en la vista previa y reiniciar variable
        currentVoucherBalance = 0;
        document.getElementById('voucherSection').style.display = 'none';
        const voucherBalanceRow = document.getElementById('previewVoucherBalanceRow');
        if (voucherBalanceRow) {
            voucherBalanceRow.style.display = 'none';
        }
        
        initPayments();
        
        const previewItems = document.getElementById('previewItems');
        if (previewItems) {
            if (cart.length === 0) {
                previewItems.innerHTML = '<tr class="empty-row"><td colspan="5">Productos aparecerán aquí</td></tr>';
            }
        }
        const previewTotal = document.getElementById('previewTotal');
        if (previewTotal) {
            previewTotal.textContent = `S/ ${getCartTotal().toFixed(2)}`;
        }
        const previewTitle = document.getElementById('previewTitle');
        if (previewTitle) {
            previewTitle.textContent = 'NOTA DE VENTA';
        }
}

    // Category buttons click handlers
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const category = this.dataset.category;
            const search = document.getElementById('searchInput').value.toLowerCase();
            document.querySelectorAll('.product-card').forEach(card => {
                const cardCategory = card.dataset.category ? card.dataset.category.trim() : '';
                const cardName = card.dataset.name ? card.dataset.name.toLowerCase() : '';
                const matchesCategory = category === 'Todos' || cardCategory === category || (category && cardCategory.toLowerCase() === category.toLowerCase());
                const matchesSearch = !search || cardName.includes(search);
                card.style.display = matchesCategory && matchesSearch ? 'block' : 'none';
            });
        });
    });
    
    // Category search input - filters buttons and products
    const categorySearchInput = document.getElementById('categorySearchInput');
    const categoryButtonsContainer = document.querySelector('.category-buttons');
    
    function filterProductsByCategory(categoryFilter) {
        const searchQuery = document.getElementById('searchInput').value.toLowerCase();
        allProductCards.forEach(card => {
            const cardCategory = card.dataset.category ? card.dataset.category.trim().toLowerCase() : '';
            const cardName = card.dataset.name ? card.dataset.name.toLowerCase() : '';
            
            let matchesCategory = false;
            if (!categoryFilter || categoryFilter === '') {
                const activeBtn = document.querySelector('.category-btn.active');
                const activeCategory = activeBtn ? activeBtn.dataset.category.toLowerCase() : 'todos';
                matchesCategory = activeCategory === 'todos' || cardCategory === activeCategory;
            } else {
                matchesCategory = cardCategory === categoryFilter || cardCategory.includes(categoryFilter);
            }
            
            const matchesSearch = !searchQuery || cardName.includes(searchQuery);
            card.style.display = matchesCategory && matchesSearch ? 'block' : 'none';
        });
    }
    
    if (categorySearchInput && categoryButtonsContainer) {
        categorySearchInput.addEventListener('input', function() {
            const filter = this.value.toLowerCase().trim();
            
            // Filter category buttons visibility
            document.querySelectorAll('.category-btn').forEach(btn => {
                const categoryName = btn.dataset.category.toLowerCase();
                if (filter === '' || categoryName.includes(filter)) {
                    btn.classList.remove('hidden');
                } else {
                    btn.classList.add('hidden');
                }
            });
            
            // Auto-select matching category button when exactly one matches
            if (filter) {
                const matchingBtns = Array.from(document.querySelectorAll('.category-btn')).filter(btn => 
                    btn.dataset.category.toLowerCase().includes(filter)
                );
                document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
                if (matchingBtns.length === 1) {
                    matchingBtns[0].classList.add('active');
                } else if (matchingBtns.length > 1) {
                    const todosBtn = Array.from(document.querySelectorAll('.category-btn')).find(b => 
                        b.dataset.category.toLowerCase() === 'todos'
                    );
                    if (todosBtn) todosBtn.classList.add('active');
                }
            } else {
                const defaultBtn = document.querySelector('.category-btn:not(.hidden)');
                if (defaultBtn) defaultBtn.classList.add('active');
            }
            
            // Filter products based on typed category
            filterProductsByCategory(filter);
        });
        
        // Also filter products when category input is cleared
        categorySearchInput.addEventListener('change', function() {
            filterProductsByCategory(this.value.toLowerCase().trim());
        });
    }
    
    // View toggle (grid/list)
    const productsGrid = document.getElementById('productsGrid');
    if (productsGrid) productsGrid.classList.add('grid-view');
    document.querySelectorAll('.product-card').forEach(card => card.classList.add('grid-card'));
    
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const viewType = this.dataset.view;
            
            if (productsGrid) {
                productsGrid.classList.remove('grid-view', 'list-view');
                productsGrid.classList.add(viewType + '-view');
            }
            
            document.querySelectorAll('.product-card').forEach(card => {
                card.classList.remove('grid-card', 'list-card');
                card.classList.add(viewType + '-card');
            });
        });
    });
    
    // Product search with autocomplete
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    allProductCards = document.querySelectorAll('.product-card');
    
    function addToCartFromCard(cardElement) {
        const id = parseInt(cardElement.dataset.id);
        const name = cardElement.dataset.name;
        const price = parseFloat(cardElement.dataset.price);
        const category = cardElement.dataset.category;
        
        const existingItem = cart.find(item => item.id === id);
        if (existingItem) {
            existingItem.quantity++;
        } else {
            cart.push({ id, name, price, quantity: 1 });
        }
        updateCart();
        showToast(`${name} agregado al carrito`);
    }
    
    searchInput?.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        const activeCategoryBtn = document.querySelector('.category-btn.active');
        const activeCategory = activeCategoryBtn ? activeCategoryBtn.dataset.category : 'Todos';
        
        allProductCards.forEach(card => {
            const cardCategory = card.dataset.category ? card.dataset.category.trim() : '';
            const cardName = card.dataset.name ? card.dataset.name.toLowerCase() : '';
            const matchesCategory = activeCategory === 'Todos' || cardCategory === activeCategory || (activeCategory && cardCategory.toLowerCase() === activeCategory.toLowerCase());
            const matchesSearch = query === '' || cardName.includes(query);
            card.style.display = matchesCategory && matchesSearch ? 'block' : 'none';
        });
        
        if (query.length >= 2 && searchResults) {
            let matches = [];
            const activeCategory = activeCategoryBtn ? activeCategoryBtn.dataset.category : 'Todos';
            allProductCards.forEach(card => {
                const cardCategory = card.dataset.category ? card.dataset.category.trim() : '';
                const cardName = card.dataset.name ? card.dataset.name.toLowerCase() : '';
                const matchesCat = activeCategory === 'Todos' || cardCategory === activeCategory || (activeCategory && cardCategory.toLowerCase() === activeCategory.toLowerCase());
                if (matchesCat && cardName.includes(query)) {
                    matches.push({ name: card.dataset.name, price: card.dataset.price, id: card.dataset.id });
                }
            });
            
            if (matches.length > 0) {
                searchResults.style.display = 'block';
                searchResults.innerHTML = matches.slice(0, 8).map(p => `<div class="search-result-item" data-id="${p.id}"><span class="search-result-name">${p.name}</span><span class="search-result-price">S/ ${parseFloat(p.price).toFixed(2)}</span></div>`).join('');
                
                searchResults.querySelectorAll('.search-result-item').forEach(item => {
                    item.addEventListener('click', function() {
                        const id = parseInt(this.dataset.id);
                        const product = Array.from(allProductCards).find(c => parseInt(c.dataset.id) === id);
                        if (product) {
                            addToCartFromCard(product);
                            searchInput.value = '';
                            searchResults.style.display = 'none';
                        }
                    });
                });
            } else {
                searchResults.style.display = 'none';
            }
        } else {
            searchResults.style.display = 'none';
        }
    });
    
    document.addEventListener('click', function(e) {
        if (searchResults && searchInput && !searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
    
    setTimeout(() => { const welcomeToast = document.getElementById('welcomeToast'); if (welcomeToast) { welcomeToast.style.animation = 'slideOut 0.4s ease forwards'; } }, 4000);

    // Settings Panel
    const settingsBtn = document.getElementById('settingsBtn');
    const settingsPanel = document.getElementById('settingsPanel');
    const settingsOverlay = document.getElementById('settingsOverlay');
    const settingsClose = document.getElementById('settingsClose');
    const btnSettingsExit = document.getElementById('btnSettingsExit');

    settingsBtn.addEventListener('click', () => {
        settingsPanel.classList.add('active');
        settingsOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    });

    function closeSettingsPanel() {
        settingsPanel.classList.remove('active');
        settingsOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    settingsClose.addEventListener('click', closeSettingsPanel);
    settingsOverlay.addEventListener('click', closeSettingsPanel);

    btnSettingsExit.addEventListener('click', function() {
        closeSettingsPanel();
        window.location.href = '{{ route("vendeya.logout") }}';
    });

    // View Cash Detail
    const btnViewCash = document.getElementById('btnViewCash');
    const cashDetailModal = document.getElementById('cashDetailModal');
    const cashDetailBody = document.getElementById('cashDetailBody');

    function closeCashDetailModal() {
        cashDetailModal.classList.remove('active');
        document.body.style.overflow = '';
    }

    document.querySelector('.cash-detail-close').addEventListener('click', closeCashDetailModal);
    cashDetailModal.addEventListener('click', function(e) {
        if (e.target === cashDetailModal) closeCashDetailModal();
    });

    function formatCurrency(value) {
        return 'S/ ' + (parseFloat(value) || 0).toFixed(2);
    }

    function formatDate(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(dateStr + 'T00:00:00');
        return d.toLocaleDateString('es-PE', { year: 'numeric', month: 'long', day: 'numeric' });
    }

    function formatTime(timeStr) {
        if (!timeStr) return '—';
        return timeStr.substring(0, 5);
    }

    function renderCashDetail(record) {
        const state = record.state === true || record.state === '1';
        cashDetailBody.innerHTML = `
            <div class="cash-detail-grid">
                <div class="cash-detail-item full">
                    <div class="label">Estado</div>
                    <div class="value ${state ? 'success' : 'danger'}">${state ? 'Abierta' : 'Cerrada'}</div>
                </div>
                <div class="cash-detail-item">
                    <div class="label">Fecha de apertura</div>
                    <div class="value">${formatDate(record.date_opening)}</div>
                    <div class="sub">${formatTime(record.time_opening)}</div>
                </div>
                <div class="cash-detail-item">
                    <div class="label">Monto inicial</div>
                    <div class="value">${formatCurrency(record.beginning_balance)}</div>
                </div>
                <div class="cash-detail-item">
                    <div class="label">Ingresos</div>
                    <div class="value success">${formatCurrency(record.income)}</div>
                </div>
                <div class="cash-detail-item">
                    <div class="label">Monto final</div>
                    <div class="value warning">${formatCurrency(record.final_balance)}</div>
                </div>
                ${record.date_closed ? `
                <div class="cash-detail-item full">
                    <div class="label">Fecha de cierre</div>
                    <div class="value">${formatDate(record.date_closed)}</div>
                    <div class="sub">${formatTime(record.time_closed)}</div>
                </div>
                ` : ''}
            </div>
        `;
    }

    if (btnViewCash) {
        btnViewCash.addEventListener('click', async function() {
            closeSettingsPanel();
            cashDetailModal.classList.add('active');
            document.body.style.overflow = 'hidden';
            cashDetailBody.innerHTML = '<div class="cash-detail-loading"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';

            try {
                const response = await fetch('{{ route("vendeya.cash.records") }}');
                const data = await response.json();
                const records = data.data || [];
                const current = records.find(r => r.state === true || r.state === '1') || records[0] || null;

                if (current) {
                    renderCashDetail(current);
                } else {
                    cashDetailBody.innerHTML = '<p style="text-align:center;padding:40px;color:var(--text-secondary);">No hay registros de caja</p>';
                }
            } catch (error) {
                cashDetailBody.innerHTML = '<p style="text-align:center;padding:40px;color:#ef4444;">Error al cargar los datos</p>';
            }
        });
    }

    // Cash Opening Modal - Only show if cash is actually closed
    const cashModal = document.getElementById('cashModal');
    const cashOpened = @json($cashOpened);
    const btnOpenCash = document.getElementById('btnOpenCash');
    const initialAmountInput = document.getElementById('initialAmount');
    const closeCashOption = document.getElementById('closeCashOption');

    console.log('Cash Status:', cashOpened);

    // Only show modal if cash is NOT open (truly closed)
    if (closeCashOption) {
        closeCashOption.addEventListener('click', async function() {
            closeSettingsPanel();
            
            const result = await Swal.fire({
                title: 'Cerrar Caja',
                text: '¿Estás seguro de que deseas cerrar la caja?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, cerrar caja',
                cancelButtonText: 'Cancelar'
            });

            if (result.isConfirmed) {
                try {
                    const response = await fetch('{{ route("vendeya.cash.close") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({})
                    });

                    const data = await response.json();

                    if (data.success) {
                        if (data.report_url) {
                            window.open(data.report_url, '_blank');
                        }
                        Swal.fire({ icon: 'success', title: 'Caja cerrada', text: data.message + (data.report_url ? ' - Imprimiendo reporte...' : ''), timer: 3000, showConfirmButton: false });
                        setTimeout(() => window.location.reload(), 3000);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                    }
                } catch (error) {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
                }
            }
        });
    }

    // Only show modal if cash is NOT open (truly closed)
    if (!cashOpened) {
        console.log('Caja cerrada - mostrando modal de apertura');
        cashModal.classList.add('active');
    } else {
        console.log('Caja abierta - ocultando modal de apertura');
        cashModal.classList.remove('active');
    }

    // Force cash opening before any other action
    btnOpenCash.addEventListener('click', async function() {
        const initialAmount = parseFloat(initialAmountInput.value) || 0;
        
        if (initialAmount < 0) {
            Swal.fire({ icon: 'error', title: 'Monto inválido', text: 'El monto inicial no puede ser negativo' });
            return;
        }

        btnOpenCash.disabled = true;
        btnOpenCash.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Abriendo...';

        try {
            const response = await fetch('{{ route("vendeya.cash.open") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ initial_amount: initialAmount })
            });

            const data = await response.json();

            if (data.success) {
                cashModal.classList.remove('active');
                Swal.fire({ icon: 'success', title: 'Caja abierta', text: data.message, timer: 2000, showConfirmButton: false });
            } else {
                console.log('Cash error:', data);
                let errorMsg = data.message || 'No se pudo abrir la caja';
                if (data.debug) {
                    errorMsg = data.debug.message || JSON.stringify(data.debug);
                }
                Swal.fire({ icon: 'error', title: 'Error', text: errorMsg });
            }
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
        }

        btnOpenCash.disabled = false;
        btnOpenCash.innerHTML = '<i class="fas fa-lock-open"></i> Abrir Caja';
    });

    // New Customer Modal
    const newCustomerModal = document.getElementById('newCustomerModal');
    const btnAddCustomer = document.getElementById('btnAddCustomer');
    const closeCustomerModal = document.getElementById('closeCustomerModal');

    if (btnAddCustomer) {
        btnAddCustomer.addEventListener('click', function() {
            newCustomerModal.classList.add('active');
        });
    }

    if (closeCustomerModal) {
        closeCustomerModal.addEventListener('click', function() {
            newCustomerModal.classList.remove('active');
        });
    }

    newCustomerModal.addEventListener('click', function(e) {
        if (e.target === newCustomerModal) {
            newCustomerModal.classList.remove('active');
        }
    });

    // Document search via apiperu.dev
    const docNumberInput = document.getElementById('customerDocNumber');
    const docTypeSelect = document.getElementById('customerDocType');
    const searchBtn = document.getElementById('btnSearchDoc');
    const searchFeedback = document.getElementById('searchDocFeedback');

    let searchTimeout;

    function clearSearchFeedback() {
        searchFeedback.textContent = '';
        searchFeedback.className = 'search-feedback';
    }

    function showSearchFeedback(message, type) {
        searchFeedback.textContent = message;
        searchFeedback.className = 'search-feedback ' + type;
    }

    function mapDocType(value) {
        return value === '6' ? 'ruc' : 'dni';
    }

    async function lookupDocument() {
        const docNumber = docNumberInput.value.trim();
        const docType = mapDocType(docTypeSelect.value);

        if (!docNumber) {
            showSearchFeedback('Ingrese un número de documento', 'error');
            return;
        }

        searchBtn.disabled = true;
        searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        clearSearchFeedback();

        try {
            const response = await fetch('{{ route("vendeya.api.lookup.document") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ type: docType, number: docNumber })
            });

            const data = await response.json();

            if (data.success) {
                document.getElementById('customerName').value = data.name;
                if (data.trade_name) {
                    document.getElementById('customerTradeName').value = data.trade_name;
                }
                if (data.address) {
                    document.getElementById('customerAddress').value = data.address;
                }
                showSearchFeedback(data.message || 'Datos encontrados', 'success');
            } else {
                showSearchFeedback(data.message || 'No se encontraron datos', 'error');
            }
        } catch (error) {
            showSearchFeedback('Error de conexión', 'error');
        } finally {
            searchBtn.disabled = false;
            searchBtn.innerHTML = '<i class="fas fa-search"></i>';
        }
    }

    searchBtn.addEventListener('click', lookupDocument);

    docNumberInput.addEventListener('input', function() {
        clearSearchFeedback();
        if (this.value.length >= 8) {
            if (searchTimeout) clearTimeout(searchTimeout);
            searchTimeout = setTimeout(lookupDocument, 600);
        }
    });

    document.getElementById('btnSaveCustomer').addEventListener('click', async function() {
        const docType = document.getElementById('customerDocType').value;
        const docNumber = document.getElementById('customerDocNumber').value;
        const name = document.getElementById('customerName').value;
        const tradeName = document.getElementById('customerTradeName').value;
        const address = document.getElementById('customerAddress').value;
        const phone = document.getElementById('customerPhone').value;
        const email = document.getElementById('customerEmail').value;

        if (!docNumber || !name) {
            Swal.fire({ icon: 'warning', title: 'Campos requeridos', text: 'Ingrese número de documento y nombre' });
            return;
        }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

        try {
            const response = await fetch('{{ route("vendeya.customer.create") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    identity_document_type_id: docType,
                    number: docNumber,
                    name: name,
                    trade_name: tradeName || '',
                    address: address || '',
                    telephone: phone || '',
                    email: email || ''
                })
            });

            const result = await response.json();

            if (result.success) {
                Swal.fire({ icon: 'success', title: 'Cliente creado', text: result.msg });
                newCustomerModal.classList.remove('active');
                
                // Add new customer to select
                const customerSelect = document.getElementById('customerSelect');
                const newOption = document.createElement('option');
                newOption.value = result.data.id || Date.now();
                newOption.textContent = `${name} - ${docNumber}`;
                customerSelect.appendChild(newOption);
                customerSelect.value = newOption.value;

                // Clear form
                document.getElementById('customerDocNumber').value = '';
                document.getElementById('customerName').value = '';
                document.getElementById('customerTradeName').value = '';
                document.getElementById('customerAddress').value = '';
                document.getElementById('customerPhone').value = '';
                document.getElementById('customerEmail').value = '';
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: result.msg || 'Error al crear cliente' });
            }
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
        }

        this.disabled = false;
        this.innerHTML = '<i class="fas fa-save"></i> Guardar Cliente';
    });

    // QZ Tray Printer Integration
    let qzPrinter = null;
    let qzConnected = false;

    async function initPrinter() {
        try {
            if (typeof qz === 'undefined') {
                console.log('QZ Tray no está instalado');
                return null;
            }
            const printers = await qz.printers.list();
            return printers[0] || null;
        } catch (error) {
            console.log('QZ Tray no disponible:', error);
            return null;
        }
    }

    async function connectQz() {
        try {
            if (typeof qz === 'undefined') return;
            await qz.websocket.connect();
            qzConnected = true;
            const printers = await qz.printers.list();
            if (printers.length > 0) {
                const saved = localStorage.getItem('qzPrinterName');
                qzPrinter = saved ? printers.find(p => p.name === saved) || printers[0] : printers[0];
            }
        } catch (e) {
            qzConnected = false;
        }
    }

    async function printThermalTicket() {
        if (!qzConnected || !qzPrinter) return;
        const thermalEl = document.getElementById('thermalTicket');
        if (!thermalEl || !thermalEl.innerHTML) return;
        try {
            const data = [{ type: 'html', format: 'plain', data: thermalEl.innerHTML }];
            await qz.printers.print(qzPrinter, data);
        } catch (e) {
            console.error('Error al imprimir:', e);
        }
    }

    // Auto-connect on page load
    setTimeout(connectQz, 1000);

    document.getElementById('btnSelectPrinter')?.addEventListener('click', async function() {
        try {
            if (typeof qz === 'undefined') {
                Swal.fire({ 
                    icon: 'warning', 
                    title: 'QZ Tray no instalado', 
                    text: 'Instale QZ Tray desde https://qz.io/download/',
                    confirmButtonText: 'Descargar'
                });
                return;
            }
            if (!qzConnected) {
                await qz.websocket.connect();
                qzConnected = true;
            }
            const printers = await qz.printers.list();
            if (printers.length === 0) {
                Swal.fire({ icon: 'warning', title: 'Sin impresoras', text: 'No se detectaron impresoras' });
                return;
            }
            const choices = {};
            printers.forEach(p => { choices[p.name] = p.name; });
            Swal.fire({
                title: 'Seleccionar impresora',
                input: 'select',
                inputOptions: choices,
                inputValue: qzPrinter ? qzPrinter.name : printers[0].name,
                confirmButtonText: 'Seleccionar',
                showCancelButton: true
            }).then(result => {
                if (result.isConfirmed) {
                    const selected = printers.find(p => p.name === result.value);
                    if (selected) {
                        qzPrinter = selected;
                        localStorage.setItem('qzPrinterName', selected.name);
                        Swal.fire({ icon: 'success', title: 'Impresora seleccionada', text: selected.name });
                    }
                }
            });
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error al conectar con QZ Tray' });
        }
    });
    
    // Voucher logic
    function refreshVoucherBalance() {
        const customerSearch = document.getElementById('customerSearch');
        if (customerSearch) {
            const parts = customerSearch.value.split(' - ');
            const doc = parts.length > 1 ? parts[parts.length - 1].trim() : '';
            if (doc) {
                fetch('{{ route("vendeya.api.vouchers.balance", ["doc" => "__DOC__"]) }}'.replace('__DOC__', doc))
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            const el = document.getElementById('voucherBalance');
                            if (el) el.value = 'S/ ' + parseFloat(data.balance).toFixed(2);
                            const previewEl = document.getElementById('previewVoucherBalance');
                            const row = document.getElementById('previewVoucherBalanceRow');
                            if (data.balance > 0) {
                                if (row) row.style.display = 'flex';
                                if (previewEl) previewEl.textContent = parseFloat(data.balance).toFixed(2);
                            } else {
                                if (row) row.style.display = 'none';
                            }
                        }
                    })
                    .catch(() => {});
            }
        }
    }

    const voucherSection = document.getElementById('voucherSection');
    const voucherCustomerDoc = document.getElementById('voucherCustomerDoc');
    const voucherBalance = document.getElementById('voucherBalance');
    const btnCheckVoucher = document.getElementById('btnCheckVoucher');

    if (btnCheckVoucher) {
        btnCheckVoucher.addEventListener('click', async function() {
            const doc = voucherCustomerDoc.value.trim();
            if (!doc) {
                Swal.fire({ icon: 'warning', title: 'Documento requerido', text: 'Ingrese el DNI o RUC del cliente' });
                return;
            }

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Consultando...';

            try {
                const response = await fetch('{{ route("vendeya.api.vouchers.balance", ["doc" => "__DOC__"]) }}'.replace('__DOC__', doc), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    voucherBalance.value = 'S/ ' + parseFloat(result.balance).toFixed(2);
                    currentVoucherBalance = parseFloat(result.balance);
                    const voucherBalanceRow = document.getElementById('previewVoucherBalanceRow');
                    const previewVoucherBalance = document.getElementById('previewVoucherBalance');
                    if (voucherBalanceRow && previewVoucherBalance) {
                        voucherBalanceRow.style.display = 'flex';
                        previewVoucherBalance.textContent = 'S/ ' + currentVoucherBalance.toFixed(2);
                    }
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: result.error || 'No se pudo consultar el saldo' });
                    voucherBalance.value = 'S/ 0.00';
                    currentVoucherBalance = 0;
                    const voucherBalanceRow = document.getElementById('previewVoucherBalanceRow');
                    if (voucherBalanceRow) {
                        voucherBalanceRow.style.display = 'none';
                    }
                }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
                voucherBalance.value = 'S/ 0.00';
            }

            this.disabled = false;
            this.innerHTML = '<i class="fas fa-search"></i> Consultar Saldo';
        });
    }
});
</script>
@endpush
