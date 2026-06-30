import { useState } from 'react';
import config, { t } from '../../config.js';
import { fieldsForStep } from '../../lib/fields.js';
import { useCheckout } from '../../context/CheckoutContext.jsx';
import FieldRenderer from '../FieldRenderer.jsx';
import AddressSearch from '../AddressSearch.jsx';
import SavedAddressList from '../SavedAddressList.jsx';
import SaveAddressControl from '../SaveAddressControl.jsx';
import StepSummary from '../StepSummary.jsx';
import ShippingRates from '../ShippingRates.jsx';
import { MapPinIcon } from '../ui/Icons.jsx';

export default function ShippingStep({ onEdit }) {
  const fields = fieldsForStep(2);
  const addressSearch = config.flags && config.flags.address_search;
  const { savedAddresses, savedAddressesEnabled, applySavedAddress } = useCheckout();
  const hasSavedAddresses = savedAddressesEnabled && savedAddresses.length > 0;

  // Which saved address is active ('' = entering a new address) and whether the
  // form is force-shown for editing the selected saved address.
  const [selectedId, setSelectedId] = useState('');
  const [editing, setEditing] = useState(false);

  // With saved addresses, the form is hidden while one is selected — unless the
  // shopper hits "Edit" on its card or switches to a new address.
  const showForm = !hasSavedAddresses || selectedId === '' || editing;

  const selectSaved = (address) => {
    setSelectedId(address.id);
    setEditing(false);
    applySavedAddress(address).catch(() => {});
  };

  const editSaved = (address) => {
    if (selectedId !== address.id) {
      setSelectedId(address.id);
      applySavedAddress(address).catch(() => {});
    }

    setEditing(true);
  };

  const useNewAddress = () => {
    setSelectedId('');
    setEditing(false);
  };

  return (
    <div className="space-y-6">
      <StepSummary sections={['contact']} onEdit={onEdit} />

      <h2 className="fc-heading text-xl text-slate-800">
        {t('shipping_address', 'Endereço de entrega')}
      </h2>

      {hasSavedAddresses && (
        <SavedAddressList
          selectedId={selectedId}
          onSelect={selectSaved}
          onEdit={editSaved}
          onUseNew={useNewAddress}
        />
      )}

      {showForm && (
        <div className="space-y-5 rounded-xl border border-slate-200 p-4">
          <div className="flex items-center gap-2 text-sm font-medium text-slate-800">
            <MapPinIcon className="h-4 w-4 fc-primary-text" />
            {editing ? t('edit_address', 'Editar endereço') : t('new_address', 'Novo endereço')}
          </div>

          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            {addressSearch && <AddressSearch />}
            {fields.map((field) => (
              <FieldRenderer key={field.id} field={field} />
            ))}
          </div>
        </div>
      )}

      {savedAddressesEnabled && showForm && selectedId === '' && <SaveAddressControl />}

      <ShippingRates />
    </div>
  );
}
