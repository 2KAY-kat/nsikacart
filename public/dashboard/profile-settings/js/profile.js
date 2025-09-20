// html dynamic content and templating
import { settingsContainer } from "./profile-data";

let settingsContainerHTML = '';

settingsContainer.forEach((tabContainer) => {
    settingsContainerHTML += `
    
        <div class="header">
            <button type="button" class="back-btn"><a href="../index.html">←</a></button>
            <h2>Account Settings</h2>
            <button class="logout-btn" id="logout-btn">Log Out</button>
        </div>

        <div class="tabs">
            <button class="tab-btn active"> <i class="fa fa-user"></i> </button>
            <button class="tab-btn"> <i class="fa fa-shield"></i> </button>
            <button class="tab-btn"> <i class="fa fa-location-dot"></i> </button>
            <button class="tab-btn"> <i class="fa fa-bell"></i> </button>
        </div>

        <div class="tab-content">
            <div class="tab-pane active">
                <form>
                    <div>
                        <label>Username</label>
                        <input type="text" placeholder="">
                    </div>
                    <div>
                        <label>Email</label>
                        <input type="email" placeholder="">
                    </div>
                    <div>
                        <label>Phone Number</label>
                        <input type="text" placeholder="">
                    </div>
                    
                    <button class="save-btn" type="submit">Save Changes</button>
                </form>
            </div>

            <div class="tab-pane">
                <form>
                    <div>
                        <label>Current Password</label>
                        <input type="password">
                    </div>
                    <div>
                        <label>New Password</label>
                        <input type="password">
                    </div>
                    <div>
                        <label>Confirm Password</label>
                        <input type="password">
                    </div>
                    <button class="save-btn" type="submit">Update Password</button>
                </form>
            </div>

            <div class="tab-pane">
                <div class="address-box">
                    <p><strong>Location</strong></p>
                    <p>Blantyre, Malawi</p>
                    <button>Edit</button>
                </div>
                <button class="save-btn">Add New Address</button>
            </div>

            <div class="tab-pane">
                <form>
                    <div class="checkbox-group">
                        <input type="checkbox" id="email">
                        <label for="email">Email Notifications</label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="sms">
                        <label for="sms">SMS Alerts</label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="orders">
                        <label for="orders">Order Updates</label>
                    </div>
                    <button class="save-btn" type="submit">Save Preferences</button>
                </form>
            </div>
        </div>

        <div id="toast" class="toast"></div>
    
    `;
});

document.querySelector('.settings-container').innerHTML = settingsContainerHTML;

// tabbing fimctionalities
const tabs = document.querySelectorAll(".tab-btn");
const panes = document.querySelectorAll(".tab-pane");

tabs.forEach((tab, index) => {
    tab.addEventListener("click", () => {
        tabs.forEach(btn => btn.classList.remove("active"));
        panes.forEach(p => p.classList.remove("active"));
        tab.classList.add("active");
        panes[index].classList.add("active");
    });
});