from fastapi import APIRouter, Depends, HTTPException, Request
from fastapi.responses import HTMLResponse
from sqlalchemy.orm import Session

from ..database import get_db
from ..models import User, Role, Order, OrderStatus
from ..security import require_role
from ..notifications import send_push
from ..settings import settings

from jinja2 import Environment, BaseLoader

router = APIRouter(prefix="/admin", tags=["admin"])


TEMPLATE = Environment(loader=BaseLoader()).from_string("""
<!doctype html>
<html>
  <head>
    <meta charset="utf-8"/>
    <title>Grabbasket Admin</title>
    <style>
      body { font-family: Arial; margin: 20px; }
      table { border-collapse: collapse; width: 100%; }
      th, td { border: 1px solid #ddd; padding: 8px; }
      th { background: #f3f3f3; }
      .pill { padding: 2px 8px; border-radius: 999px; background: #eee; }
      form { display: inline; }
    </style>
  </head>
  <body>
    <h2>Orders</h2>
    <table>
      <thead>
        <tr>
          <th>ID</th><th>Status</th><th>Vendor</th><th>Customer</th><th>Partner</th><th>Action</th>
        </tr>
      </thead>
      <tbody>
      {% for o in orders %}
        <tr>
          <td>{{ o.id }}</td>
          <td><span class="pill">{{ o.status }}</span></td>
          <td>{{ o.vendor_id }}</td>
          <td>{{ o.customer_id }}</td>
          <td>{{ o.partner_id or "" }}</td>
          <td>
            {% if o.status == "ACCEPTED_BY_SELLER" %}
              <form method="post" action="/admin/orders/{{o.id}}/assign">
                <button type="submit">Assign partner</button>
              </form>
            {% endif %}
          </td>
        </tr>
      {% endfor %}
      </tbody>
    </table>
  </body>
</html>
""")


@router.get("/panel", response_class=HTMLResponse)
def panel(
    request: Request,
    _: User = Depends(require_role(Role.ADMIN)),
    db: Session = Depends(get_db),
):
    if not settings.admin_panel_enabled:
        raise HTTPException(status_code=404, detail="Admin panel disabled")

    orders = db.query(Order).order_by(Order.id.desc()).limit(200).all()
    html = TEMPLATE.render(orders=[{"id": o.id, "status": o.status.value, "vendor_id": o.vendor_id, "customer_id": o.customer_id, "partner_id": o.partner_id} for o in orders])
    return HTMLResponse(html)


@router.post("/orders/{order_id}/assign")
def assign_partner(order_id: int, _: User = Depends(require_role(Role.ADMIN)), db: Session = Depends(get_db)):
    order = db.query(Order).filter(Order.id == order_id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    if order.status != OrderStatus.ACCEPTED_BY_SELLER:
        raise HTTPException(status_code=400, detail=f"Cannot assign in status {order.status}")

    partner = db.query(User).filter(User.role == Role.PARTNER, User.is_partner_available == True).order_by(User.id.asc()).first()
    if not partner:
        raise HTTPException(status_code=400, detail="No available partner")

    order.partner_id = partner.id
    order.status = OrderStatus.ASSIGNED_TO_PARTNER
    db.commit()

    # Notify partner + customer
    p_tokens = [t.token for t in partner.device_tokens]
    send_push(p_tokens, "New delivery", f"You have a new order #{order.id}", {"order_id": order.id, "status": order.status.value})

    cust = db.query(User).filter(User.id == order.customer_id).first()
    c_tokens = [t.token for t in (cust.device_tokens if cust else [])]
    send_push(c_tokens, "Partner assigned", f"Partner assigned for order #{order.id}", {"order_id": order.id, "status": order.status.value})

    return {"ok": True, "partner_id": partner.id}
