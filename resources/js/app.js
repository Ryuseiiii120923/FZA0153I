import "./bootstrap.js";

document.addEventListener("DOMContentLoaded", () => {
    const codeReader = new ZXing.BrowserMultiFormatReader();
    const scanppf = document.getElementById("scan-ppf");
    let scanning = false;
    let closedef = document.getElementById("defect-id-close");
    let adddef = document.getElementById("addDefect");
    let addrew = document.getElementById("addRework");
    let closere = document.getElementById("rework-id-close");
    const ppf = document.getElementById("PPF");
    const inspectorInputs = document.querySelectorAll("#inspectors input");

    lockFormFields();
    lockbuttons();
    // const alreadyReloaded = localStorage.getItem("alreadyReloaded");

    // if (savedBtn && !alreadyReloaded) {
    //     // Prevent second reload loop
    //     localStorage.setItem("alreadyReloaded", "true");

    //     setTimeout(() => {
    //         const target = document.getElementById(savedBtn);
    //         if (target) target.click();
    //     }, 150);
    // } else {
    //     // Clear flags after auto-click is done
    //     localStorage.removeItem("autoClickBtn");
    //     localStorage.removeItem("alreadyReloaded");
    // }

    // ["Init-add", "Init-update", "Init-delete", "Init-inquire"].forEach((id) => {
    //     const btn = document.getElementById(id);
    //     if (!btn) return;

    //     btn.addEventListener("click", () => {
    //         localStorage.setItem("autoClickBtn", id);
    //     });
    // });

    scanppf.addEventListener("click", function () {
        if (scanning == true) {
            return;
        }
        navigator.mediaDevices
            .enumerateDevices()
            .then((devices) => {
                const videoInputDevices = devices.filter(
                    (device) => device.kind === "videoinput"
                );

                if (videoInputDevices.length === 0) {
                    alert("No Camera found.");
                    scanning = false;
                    return;
                }
                navigator.mediaDevices
                    .getUserMedia({ video: true })
                    .then(() => {
                        return navigator.mediaDevices.enumerateDevices();
                    })
                    .then((devices) => {
                        console.log(devices);
                    })
                    .catch((err) => {
                        console.error("Permission or device error:", err);
                    });

                const selectedDeviceId = videoInputDevices[1].deviceId;
                scanning = true;
                codeReader.decodeFromVideoDevice(
                    selectedDeviceId,
                    "video",
                    (result, err) => {
                        if (result) {
                            const qrcode = document.getElementById("PPF");
                            const scannedPPF = result.getText().trim();
                            qrcode.value = scannedPPF;
                            qrcode.dispatchEvent(new Event("input"));
                            Livewire.dispatch("post-ppf", { ppf: scannedPPF });

                            qrcode.focus();
                            codeReader.reset();
                            scanning = false;

                            document.getElementById("scanner-id-close").click();
                            return;
                        }
                        if (err && !(err instanceof ZXing.NotFoundException)) {
                            console.error(err);
                        }
                    }
                );
            })
            .catch((err) => {
                console.error("error", err);
                scanning = false;
            });
    });

    window.hasError = false;

    window.addEventListener("ppf-error", () => {
        window.hasError = true;
        lockFormFields();
    });

    window.addEventListener("ppf-valid", () => {
        if (window.hasError) {
            // Don't unlock if any error exists
            lockFormFields();
            return;
        }

        if (!ppf.checkValidity()) {
            ppf.reportValidity();
            lockFormFields();
            return;
        }

        window.hasError = false;
        unlockFormFields();
    });

    ppf.addEventListener("keydown", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            if (window.hasError) {
                lockFormFields();
                return;
            }
            Livewire.dispatch("post-ppf", { ppf: ppf.value.trim() });
            Livewire.dispatch("update-ppf", { ppf: ppf.value.trim() });
        }
    });

    window.addEventListener("confirm-delete", () => {
        if (confirm("Are you sure you want to delete this?")) {
            Livewire.dispatch("DeleteToDb");
        }
    });
    document.addEventListener("livewire:navigated", () => {
        initGoodNgInputs();
    });
    document.addEventListener("livewire:load", () => {
        initGoodNgInputs();
        lockbuttons();
    });
    document.addEventListener("livewire:updated", () => {
        initGoodNgInputs();
        lockbuttons();
    });

    Livewire.on("set-readonly", (mode) => makeReadOnlyAll(mode));
    // window.addEventListener("enable-buttons", enableButtons);

    function lockbuttons() {
        document.getElementById("add-rework").disabled = true;
        document.getElementById("add-defect").disabled = true;
        document.getElementById("scan-ppf").disabled = true;
        document.getElementById("PPF").readOnly = true;
        document.getElementById("PPF").classList.add("bg-gray-500");
        document.getElementById("OuterPanel").classList.add("blur-sm");
        document
            .getElementById("OuterPanel")
            .classList.add("pointer-events-none");
    }
    function resetActionButtons() {
        const buttons = [
            "Init-add",
            "Init-update",
            "Init-delete",
            "Init-inquire",
        ];
        const submitbtn = document.getElementById("SubmitBtn");

        submitbtn.classList.remove(
            "bg-green-700",
            "hover:bg-green-800",
            "focus:outline-none",
            "focus:ring-4",
            "focus:ring-green-300",
            "bg-blue-700",
            "hover:bg-blue-800",
            "focus:outline-none",
            "focus:ring-4",
            "focus:ring-blue-300",
            "bg-red-700",
            "hover:bg-red-800",
            "focus:outline-none",
            "focus:ring-4",
            "focus:ring-red-300",
            "bg-yellow-900",
            "scale-95",
            "shadow-inner",
            "transition-all",
            "border-2",
            "border-double",
            "border-yellow-400"
        );

        buttons.forEach((id) => {
            const btn = document.getElementById(id);
            if (!btn) return;

            btn.classList.remove(
                "scale-95",
                "shadow-inner",
                "border-2",
                "border-double",
                "border-green-400",
                "border-blue-400",
                "border-red-400",
                "bg-green-900",
                "bg-blue-900",
                "bg-red-900"
            );
        });
    }
    window.addEventListener("enable-buttons", () => {
        enableButtons();
        // lockAction();
    });
    window.addEventListener("addbutton", () => {
        lockFormFields();
        resetActionButtons();
        document.getElementById("SubmitBtn").textContent = "Add";
        document.getElementById("SubmitBtn").hidden = false;
        document
            .getElementById("SubmitBtn")
            .classList.add(
                "bg-green-700",
                "hover:bg-green-800",
                "focus:outline-none",
                "focus:ring-4",
                "focus:ring-green-300"
            );
        document
            .getElementById("Init-add")
            .classList.add(
                "bg-green-900",
                "scale-95",
                "shadow-inner",
                "transition-all",
                "border-2",
                "border-double",
                "border-green-400"
            );
    });
    document.addEventListener('haserror', event => {
    let message = null;

    // Livewire 3 can wrap the data in __livewire or send directly
    if (event.detail[0]?.__livewire?.params?.[0]?.message) {
        // wrapped version
        message = event.detail[0].__livewire.params[0].message;
    } else if (event.detail[0]?.message) {
        // direct version
        message = event.detail[0].message;
    } else if (event.detail?.message) {
        // sometimes detail itself is the object
        message = event.detail.message;
    }

    if (message) {
        alert(message);
    } else {
        console.error('Livewire event did not contain a message:', event);
    }
});

    window.addEventListener("editbutton", () => {
        resetActionButtons();
        document.getElementById("SubmitBtn").textContent = "Edit";
        document.getElementById("SubmitBtn").hidden = false;
        document
            .getElementById("SubmitBtn")
            .classList.add(
                "bg-blue-700",
                "hover:bg-blue-800",
                "focus:outline-none",
                "focus:ring-4",
                "focus:ring-blue-300"
            );
        document
            .getElementById("Init-update")
            .classList.add(
                "bg-blue-900",
                "scale-95",
                "shadow-inner",
                "transition-all",
                "border-2",
                "border-double",
                "border-blue-400"
            );
    });

    window.addEventListener("deletebutton", () => {
        resetActionButtons();
        document.getElementById("SubmitBtn").textContent = "Delete";
        document.getElementById("SubmitBtn").hidden = false;
        document
            .getElementById("SubmitBtn")
            .classList.add(
                "bg-red-700",
                "hover:bg-red-800",
                "focus:outline-none",
                "focus:ring-4",
                "focus:ring-red-300"
            );
        document
            .getElementById("Init-delete")
            .classList.add(
                "bg-red-900",
                "scale-95",
                "shadow-inner",
                "transition-all",
                "border-2",
                "border-double",
                "border-red-400"
            );
    });
    window.addEventListener("viewbutton", () => {
        resetActionButtons();
        document
            .getElementById("Init-inquire")
            .classList.add(
                "bg-yellow-900",
                "scale-95",
                "shadow-inner",
                "transition-all",
                "border-2",
                "border-double",
                "border-yellow-400"
            );
    });
    function enableButtons() {
        const fieldss = ["add-rework", "add-defect", "scan-ppf"];
        fieldss.forEach((ids) => {
            const els = document.getElementById(ids);
            if (els) {
                els.disabled = false;
            }
        });
        document.getElementById("PPF").readOnly = false;
        document.getElementById("PPF").classList.remove("bg-gray-500");
        document.getElementById("OuterPanel").classList.remove("blur-sm");

        document
            .getElementById("OuterPanel")
            .classList.remove("pointer-events-none");
    }

    // function persistAction(action, buttonId) {
    //     sessionStorage.setItem("lastAction", action);
    //     sessionStorage.setItem("lastButtonId", buttonId);
    // }

    // function restoreAction() {
    //     const action = sessionStorage.getItem("lastAction");
    //     const buttonId = sessionStorage.getItem("lastButtonId");
    //     if (!action || !buttonId) return;

    //     const el = document.querySelector("[wire\\:id]");
    //     if (el) {
    //         const component = Livewire.find(el.getAttribute("wire:id"));
    //         if (component) {
    //             component.call("setActionAuto", action);
    //         }
    //     }

    //     const btn = document.getElementById(buttonId);
    //     if (btn) btn.classList.add("active"); // optional styling
    // }

    // ["Init-add", "Init-update", "Init-delete", "Init-inquire"].forEach((id) => {
    //     const btn = document.getElementById(id);
    //     if (!btn) return;
    //     btn.addEventListener("click", () => {
    //         const map = {
    //             "Init-add": "Add",
    //             "Init-update": "Edit",
    //             "Init-delete": "Delete",
    //             "Init-inquire": "View",
    //         };
    //         persistAction(map[id], id);
    //     });
    // });

    // restoreAction();

    // function lockAction() {
    //     document.getElementById("buttons-action").classList.add("blur-sm");
    //     document
    //         .getElementById("buttons-action")
    //         .classList.add("pointer-events-none");
    // }
    function unlockFormFields() {
        if (window.hasError) {
            alert("Please fix the error before continuing.");
            return;
        }

        const fields = [
            "expct",
            "excss",
            "lack",
            "rework",
            "sample",
            "good",
            "ng",
            "automach",
            "plant",
            "inspectDate",
            "details",
            "upd",
            "registrant",
            "insp1",
            "insp2",
            "insp3",
            "insp4",
            "insp5",
        ];

        fields.forEach((id) => {
            const el = document.getElementById(id);
            if (el) {
                el.readOnly = false;
                el.classList.remove("bg-gray-500");
            }
        });

        inspectorInputs.forEach((input) => {
            input.readOnly = false;
            input.classList.remove("bg-gray-500");
        });

        ppf.readOnly = true;
        ppf.classList.add("bg-gray-300");
        document.getElementById("add-defect").focus();
    }
    function lockFormFields() {
        const fields = [
            "HfNo",
            "LotNo",
            "PartNo",
            "MatNo",
            "MoldNo",
            "PressNo",
            "shift",
            "opt",
            "expct",
            "excss",
            "lack",
            "rework",
            "sample",
            "good",
            "ng",
            "automach",
            "plant",
            "inspectDate",
            "details",
            "upd",
            "registrant",
        ];

        fields.forEach((id) => {
            const el = document.getElementById(id);
            if (el) {
                el.readOnly = true;
                el.classList.add("bg-gray-500");
            }
        });

        inspectorInputs.forEach((input) => {
            input.readOnly = true;
            input.classList.add("bg-gray-500");
        });

        ppf.focus();
    }

    document.getElementById("excss").addEventListener("focus", function () {
        this.select();
    });
    document.getElementById("lack").addEventListener("focus", function () {
        this.select();
    });
    document.getElementById("rework").addEventListener("focus", function () {
        this.select();
    });
    document.getElementById("sample").addEventListener("focus", function () {
        this.select();
    });

    function initGoodNgInputs() {
        document
            .getElementById("excss")
            .addEventListener("keydown", function () {
                if (event.key === "Enter") {
                    event.preventDefault();

                    if (document.getElementById("excss").value.trim() === "") {
                        Livewire.dispatch("setExcssQty", { value: 0 });
                    }

                    const excssValue =
                        parseFloat(document.getElementById("excss").value) || 0;

                    if (excssValue !== 0) {
                        document.getElementById("lack").value = 0;
                        document.getElementById("rework").focus();
                        // document.getElementById("lack").readOnly = true;
                        // document
                        //     .getElementById("lack")
                        //     .dispatchEvent(new Event("input"));
                    } else {
                        document.getElementById("lack").focus();
                        document.getElementById("lack").readOnly = false;
                    }
                    document.getElementById("GoodNg").click();
                }
            });

        document
            .getElementById("lack")
            .addEventListener("keydown", function () {
                if (event.key === "Enter") {
                    event.preventDefault();
                    if (document.getElementById("lack").value.trim() === "") {
                        Livewire.dispatch("setlack", { value: 0 });
                    }
                    document.getElementById("rework").focus();
                    document.getElementById("GoodNg").click();
                }
            });
        document
            .getElementById("rework")
            .addEventListener("keydown", function () {
                if (event.key === "Enter") {
                    event.preventDefault();
                    if (document.getElementById("rework").value.trim() === "") {
                        Livewire.dispatch("setrework", { value: 0 });
                    }
                    document.getElementById("sample").focus();
                    document.getElementById("GoodNg").click();
                }
            });

        document
            .getElementById("sample")
            .addEventListener("keydown", function () {
                if (event.key === "Enter") {
                    event.preventDefault();
                    if (document.getElementById("sample").value.trim() === "") {
                        Livewire.dispatch("setsample", { value: 0 });
                    }
                    document.getElementById("GoodNg").click();
                }
            });
    }

    document.addEventListener("livewire:load", () => {
        initGoodNgInputs();
    });

    adddef.addEventListener("click", function () {
        closedef.click();
    });
    addrew.addEventListener("click", function () {
        closere.click();
    });
});
