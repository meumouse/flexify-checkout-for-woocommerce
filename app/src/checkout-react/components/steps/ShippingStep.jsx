import config, { t } from '../../config.js';
import { fieldsForStep } from '../../lib/fields.js';
import FieldRenderer from '../FieldRenderer.jsx';
import AddressSearch from '../AddressSearch.jsx';
import StepSummary from '../StepSummary.jsx';
import ShippingRates from '../ShippingRates.jsx';
import { MapPinIcon } from '../ui/Icons.jsx';

export default function ShippingStep({ onEdit }) {
  const fields = fieldsForStep(2);
  const addressSearch = config.flags && config.flags.address_search;

  return (
    <div className="space-y-6">
      <StepSummary sections={['contact']} onEdit={onEdit} />

      <h2 className="fc-heading text-xl text-slate-800">
        {t('shipping_address', 'Endereço de entrega')}
      </h2>

      <div className="space-y-5 rounded-xl border border-slate-200 p-4">
        <div className="flex items-center gap-2 text-sm font-medium text-slate-800">
          <MapPinIcon className="h-4 w-4 fc-primary-text" />
          {t('new_address', 'Novo endereço')}
        </div>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          {addressSearch && <AddressSearch />}
          {fields.map((field) => (
            <FieldRenderer key={field.id} field={field} />
          ))}
        </div>
      </div>

      <ShippingRates />
    </div>
  );
}
